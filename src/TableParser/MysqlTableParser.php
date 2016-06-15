<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\TableParser\TypeInfo;
use LazyRecord\TableParser\ReferenceParser;
use LazyRecord\TableParser\TypeInfoParser;
use SQLBuilder\Raw;

class MysqlTableParser extends BaseTableParser implements ReferenceParser
{
    public function getTables()
    {
        $stm = $this->connection->query('show tables;');
        $rows = $stm->fetchAll( PDO::FETCH_NUM);
        return array_map(function($row) { return $row[0]; },$rows);
    }

    public function reverseTableSchema($table)
    {
        $stm = $this->connection->query("SHOW COLUMNS FROM $table");
        $schema = new DeclareSchema;
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
            } else if ($typeInfo->set) {
                $column->set($typeInfo->set);
            }

            if ($row['Null'] == 'NO') {
                // timestamp is set to Null=No by default.
                if ($row['Type'] !== "timestamp") {
                    $column->requried();
                    $column->notNull(true);
                }
            } else if ($row['Null'] == 'YES') {
                $column->null();
            }

            switch($row['Key']) {
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
            $extraAttributes = [ ];
            if (strtolower($row['Extra']) == 'auto_increment') {
                $column->autoIncrement();
            } else if (preg_match('/ON UPDATE CURRENT_TIMESTAMP/i', $row['Extra'])) {
                $extraAttributes['OnUpdateCurrentTimestamp'] = true;
            } else if (preg_match('/VIRTUAL GENERATED/i', $row['Extra'])) {
                $extraAttributes['VirtualGenerated'] = true;
            } else if (preg_match('/VIRTUAL STORED/i', $row['Extra'])) {
                $extraAttributes['VirtualStored'] = true;
            }
            

            // The default value returned from MySQL is string, we need the
            // type information to cast them to PHP Scalar or other
            // corresponding type
            if (NULL !== $row['Default']) {
                $default = $row['Default'];

                if ($typeInfo->type == 'boolean') {
                    if ($default == '1') {
                        $column->default(true);
                    } else if ($default == '0') {
                        $column->default(false);
                    }
                } else if ($typeInfo->isa == 'int') {
                    $column->default(intval($default));
                } else if ($typeInfo->isa == 'double') {
                    $column->default(doubleval($default));
                } else if ($typeInfo->isa == 'float') {
                    $column->default(floatval($default));
                } else if ($typeInfo->isa == 'str') {
                    $column->default($default);
                } else if ($typeInfo->type == 'timestamp') {
                    // for mysql, timestamp fields' default value is
                    // 'current_timestamp' and 'on update current_timestamp'
                    // when the two conditions are matched, we need to elimante
                    // the default value just as what we've defined in schema.
                    if (isset($extraAttributes['OnUpdateCurrentTimestamp']) && strtolower($default) == 'current_timestamp') {
                        // Do nothing
                    } else if (strtolower($default) == 'current_timestamp') {
                        $column->default(new Raw($default));
                    } else if (is_numeric($default)) {
                        $column->default(intval($default));
                    }
                } else if ($typeInfo->type == 'datetime') {
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


    public function queryReference($table)
    {
        $stm = $this->connection->query('SELECT DATABASE() FROM DUAL');
        $dbName = $stm->fetchColumn(0);

        $sql = "
        SELECT
            TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = :table_schema 
        ";
        $stm = $this->connection->prepare($sql);
            // AND REFERENCED_TABLE_NAME = :table_name 
        $stm->execute([
            ':table_schema' => $dbName,
            // ':table_name' => $table
        ]);
        $rows = $stm->fetchAll(PDO::FETCH_OBJ);
        $references = [];
        foreach ($rows as $row) {
            // CONSTRAINT_NAME = [PRIMARY, child_ibfk_1 ...  ]
            $references[$row->COLUMN_NAME] = (object) [
                'name'   => $row->CONSTRAINT_NAME,
                'table'  => $row->REFERENCED_TABLE_NAME,
                'column' => $row->REFERENCED_COLUMN_NAME,
            ];
        }
        return $references;
    }


}
