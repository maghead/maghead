<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\Schema\SchemaDeclare;
use SQLBuilder\Raw;

class SqliteTableParser extends BaseTableParser
{

    public function getTables()
    {
        $stm = $this->connection->query("SELECT * FROM sqlite_master WHERE type='table';");
        $rows = $stm->fetchAll(PDO::FETCH_OBJ);
        $tables = array();
        foreach( $rows as $row ) {
            if( $row->tbl_name == 'sqlite_sequence' )
                continue;
            $tables[] = $row->tbl_name;
        }
        return $tables;
    }

    public function getTableSql($table)
    {
        $stm = $this->connection->query("select sql from sqlite_master where type = 'table' AND name = '$table'");
        // $stm = $this->connection->query("PRAGMA table_info($table)");
        return $stm->fetch(PDO::FETCH_OBJ)->sql;
    }


    public function parseTableSql($table)
    {
        $sql = $this->getTableSql($table);
        if (preg_match('#`?(\w+)`?\s*\((.*)\)#ism',$sql,$regs) ) {
            $columns = array();
            $name = $regs[1];
            $columnstr = $regs[2];

            $parser = new SqliteTableDefinitionParser;


            $tableDef = $parser->parseColumnDefinitions($columnstr);

            print_r($columnstr);
            print_r($tableDef);
            return $tableDef;
        }
    }

    public function getTableSchema($table) 
    {
        $tableDef = $this->parseTableSql($table);

        $schema = new SchemaDeclare;
        $schema->columnNames = $schema->columns = array();


        foreach ($tableDef->columns as $columnDef) {
            $name = $columnDef->name;
            $column = $schema->column($name);

            if (! isset($columnDef->type) ) {
                var_dump( $columnDef ); 
            }

            $type = $columnDef->type;

            $column->type($type);

            $isa = $this->typenameToIsa($type);
            $column->isa($isa);

            if (isset($columnDef->notNull) && $columnDef->notNull !== null) {
                if ($columnDef->notNull) {
                    $column->notNull();
                } else {
                    $column->null();
                }
            }

            if (isset($columnDef->primary)) {
                $column->primary(true);
                $schema->primaryKey = $name;

                if ($columnDef->autoIncrement) {
                    $column->autoIncrement(true);
                }

            } else if (isset($columnDef->unique)) {
                $column->unique(true);
            }

            if (isset($columnDef->default)) {
                $default = $columnDef->default;
                if (is_scalar($default)) {
                    $column->default($default);
                } else if ($default instanceof Token && $default->type == 'literal') {
                    $column->default(new Raw($default->val));
                } else {
                    throw new Exception('Incorrect literal token');
                }
            }

        }
        return $schema;
    }
}



