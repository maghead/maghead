<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\TableParser\TypeInfo;
use LazyRecord\TableParser\TypeInfoParser;

class MysqlTableParser extends BaseTableParser
{
    public function getTables()
    {
        $stm = $this->connection->query('show tables;');
        $rows = $stm->fetchAll( PDO::FETCH_NUM);
        return array_map(function($row) { return $row[0]; },$rows);
    }

    public function getTableSchemaMap($table)
    {
        $stm = $this->connection->query("SHOW COLUMNS FROM $table");
        $schema = new SchemaDeclare;
        $schema->columnNames = $schema->columns = array();
        $schema->table($table);
        $rows = $stm->fetchAll();

        /*
        if ($table == 'users') {
            var_dump($rows); 
        }
         */

        foreach ($rows as $row) {
            $type = $row['Type'];
            $typeInfo = TypeInfoParser::parseTypeInfo($type, $this->driver);
            $isa = $typeInfo->isa;

            var_dump( $row, $typeInfo ); 
            // var_dump( $row['Field'] , $row['Type'] ); 

            $column = $schema->column($row['Field']);
            $column->type($typeInfo->type);

            if ($row['Null'] == 'YES') {
                $column->null();
            } else {
                $column->notNull();
            }

            if ($typeInfo->length) {
                $column->length($typeInfo->length);
            } 
            if ($typeInfo->precision) {
                $column->decimals($typeInfo->precision);
            }

            if ('PRI' === $row['Key']) {
                $column->primary(true);
                $schema->primaryKey = $row['Field'];
            } else if ('UNI' === $row['Key']) {
                $column->unique(true);
            }

            if ($typeInfo->isa) {
                $column->isa($typeInfo->isa);
            }

            if (NULL !== $row['Default']) {

                if ($typeInfo->type == 'boolean') {
                    if ($row['Default'] == '1') {
                        $column->default(true);
                    } else if ($row['Default'] == '0') {
                        $column->default(false);
                    }
                }
            }
        }
        return $schema;
    }


}
