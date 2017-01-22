<?php

namespace Maghead\TableParser;

use PDO;
use stdClass;
use Maghead\Schema\DeclareSchema;
use SQLBuilder\Raw;
use Maghead\Inflector;

class MysqlTableParser extends BaseTableParser implements ReferenceParser
{
    public function getTables()
    {
        $stm = $this->connection->query('show tables;');
        $rows = $stm->fetchAll(PDO::FETCH_NUM);

        return array_map(function ($row) { return $row[0]; }, $rows);
    }

    public function reverseTableSchema($table, $referenceSchema = null)
    {
        $stm = $this->connection->query("SHOW COLUMNS FROM $table");
        $schema = new DeclareSchema();
        $schema->columnNames = $schema->columns = array();
        $schema->table($table);
        $rows = $stm->fetchAll();

        foreach ($rows as $row) {
            $type = $row['Type'];
            $typeInfo = TypeInfoParser::parseTypeInfo($type, $this->driver);
            $isa = $typeInfo->isa;

            $column = $schema->column($row['Field']);
            $column->type($typeInfo->type);

            if ($typeInfo->length) {
                $column->length($typeInfo->length);
            }
            if ($typeInfo->precision) {
                $column->decimals($typeInfo->precision);
            }
            if ($typeInfo->isa) {
                $column->isa($typeInfo->isa);
            }
            if ($typeInfo->unsigned) {
                $column->unsigned();
            }

            if ($typeInfo->enum) {
                $column->enum($typeInfo->enum);
            } elseif ($typeInfo->set) {
                $column->set($typeInfo->set);
            }

            switch ($row['Null']) {
            case 'NO':
                // timestamp is set to Null=No by default.
                // However, it's possible that user didn't set notNull in the schema,
                // we should skip the check in comparator.
                if ($referenceSchema
                    && isset($row['Field'])
                    && $referenceSchema->getColumn($row['Field'])
                    && !$referenceSchema->getColumn($row['Field'])->notNull
                    && (strtolower($typeInfo->type) === 'timestamp'
                        || (isset($row['Key']) && $row['Key'] === 'PRI'))
                ) {
                } else {
                    $column->notNull(true);
                }
                break;
            case 'YES':
                $column->null();
                break;
            }

            switch ($row['Key']) {
                case 'PRI':
                    $column->primary(true);
                    $schema->primaryKey = $row['Field'];
                    break;
                // If Key is MUL, multiple occurrences of a given value are
                // permitted within the column. The column is the first
                // column of a nonunique index or a unique-valued index
                // that can contain NULL values.
                case 'MUL':
                    break;
                case 'UNI':
                    $column->unique(true);
                    break;
            }

            // Parse information from the Extra field
            // @see https://dev.mysql.com/doc/refman/5.7/en/show-columns.html
            $extraAttributes = [];
            if (strtolower($row['Extra']) == 'auto_increment') {
                $column->autoIncrement();
            } elseif (preg_match('/ON UPDATE (\w+)/i', $row['Extra'], $matches)) {
                /*
                To specify automatic properties, use the DEFAULT
                CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP clauses
                in column definitions. The order of the clauses does not
                matter. If both are present in a column definition, either
                can occur first. Any of the synonyms for CURRENT_TIMESTAMP
                have the same meaning as CURRENT_TIMESTAMP. These are
                CURRENT_TIMESTAMP(), NOW(), LOCALTIME, LOCALTIME(),
                LOCALTIMESTAMP, and LOCALTIMESTAMP().
                */
                $extraAttributes['OnUpdate'.Inflector::getInstance()->camelize(strtolower($matches[1]))] = true;
            } elseif (preg_match('/VIRTUAL GENERATED/i', $row['Extra'])) {
                $extraAttributes['VirtualGenerated'] = true;
            } elseif (preg_match('/VIRTUAL STORED/i', $row['Extra'])) {
                $extraAttributes['VirtualStored'] = true;
            }

            // The default value returned from MySQL is string, we need the
            // type information to cast them to PHP Scalar or other
            // corresponding type
            if (null !== $row['Default']) {
                $default = $row['Default'];

                if ($typeInfo->type == 'boolean') {
                    if ($default == '1') {
                        $column->default(true);
                    } elseif ($default == '0') {
                        $column->default(false);
                    }
                } elseif ($typeInfo->isa == 'int') {
                    $column->default(intval($default));
                } elseif ($typeInfo->isa == 'double') {
                    $column->default(doubleval($default));
                } elseif ($typeInfo->isa == 'float') {
                    $column->default(floatval($default));
                } elseif ($typeInfo->isa == 'str') {
                    $column->default($default);
                } elseif ($typeInfo->type == 'timestamp') {
                    // for mysql, timestamp fields' default value is
                    // 'current_timestamp' and 'on update current_timestamp'
                    // when the two conditions are matched, we need to elimante
                    // the default value just as what we've defined in schema.
                    if (isset($extraAttributes['OnUpdateCurrentTimestamp']) && strtolower($default) == 'current_timestamp') {
                        // Don't set default value
                    } elseif (strtolower($default) == 'current_timestamp') {
                        $column->default(new Raw($default));
                    } elseif (is_numeric($default)) {
                        $column->default(intval($default));
                    }
                } elseif ($typeInfo->type == 'datetime') {
                    // basically, CURRENT_TIMESTAMP, transaction_timestamp()
                    // and now() do exactly the same. CURRENT_TIMESTAMP is a
                    // syntactical oddity for a function, having no trailing
                    // pair of parentheses. That's according to the SQL
                    // standard.
                    // 
                    // @see http://dba.stackexchange.com/questions/63548/difference-between-now-and-current-timestamp
                    if (strtolower($default) == 'current_timestamp') {
                        // XXX: NOW() will be converted into current_timestamp
                        $column->default(new Raw($default));
                    }
                }
            }
        }

        return $schema;
    }

    public function queryReferences($table)
    {
        $stm = $this->connection->query('SELECT DATABASE() FROM DUAL');
        $dbName = $stm->fetchColumn(0);

        $sql = 'SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :table_schema
            AND TABLE_NAME = :table_name
        ';
        $stm = $this->connection->prepare($sql);
        $stm->execute([
            ':table_schema' => $dbName,
            ':table_name' => $table,
        ]);
        $rows = $stm->fetchAll(PDO::FETCH_OBJ);
        $references = [];
        foreach ($rows as $row) {
            // CONSTRAINT_NAME = [PRIMARY, child_ibfk_1 ...  ]
            $references[$row->COLUMN_NAME] = $this->transformReferenceInfo($row);
        }

        return $references;
    }

    protected function transformReferenceInfo(stdClass $row)
    {
        return (object) [
            'name' => $row->CONSTRAINT_NAME,
            'table' => $row->REFERENCED_TABLE_NAME,
            'column' => $row->REFERENCED_COLUMN_NAME,
        ];
    }
}
