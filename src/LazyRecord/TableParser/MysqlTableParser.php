<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\TableParser\TypeInfo;
use LazyRecord\TableParser\TypeInfoParser;
use SQLBuilder\Raw;

class MysqlTableParser extends BaseTableParser
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
        $schema = new SchemaDeclare;
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

            if ($row['Null'] == 'YES') {
                $column->null();
            } else {
                $column->notNull();
            }

            switch($row['Key']) {
                case 'PRI':
                    $column->primary(true);
                    $schema->primaryKey = $row['Field'];
                    break;
                case 'MUL':
                    break;
                case 'UNI':
                    $column->unique(true);
                    break;
            }


            if (strtolower($row['Extra']) === 'auto_increment') {
                $column->autoIncrement();
            }

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
                } else if ($typeInfo->isa == 'DateTime') {
                    if (strtolower($default) == 'current_timestamp') {
                        $column->default(new Raw($default));
                    } else if (is_numeric($default)) {
                        $column->default(intval($default));
                    }
                }
            }
        }
        return $schema;
    }


}
