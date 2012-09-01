<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\Schema\SchemaDeclare;

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
        return $stm->fetch(PDO::FETCH_OBJ)->sql;
    }


    public function parseTableSql($table)
    {
        $sql = $this->getTableSql($table);
        if( preg_match('#(\w+)\s*\((.*)\)#ism',$sql,$regs) ) {
            $columns = array();
            $name = $regs[1];
            $columnstr = $regs[2];
            $columnsqls = explode(',',$columnstr);

            foreach( $columnsqls as $columnsql ) {
                $column = array();
                $parts = preg_split('#\s+#',$columnsql,0,PREG_SPLIT_NO_EMPTY);
                $column['name'] = $parts[0];
                $column['type'] = $parts[1];

                if( in_array('primary',$parts) ) {
                    $column['pk'] = true;
                }
                if( in_array('unique',$parts) ) {
                    $column['unique'] = true;
                }
                $p = array_search('default',$parts);
                if( $p !== false ) {
                    $column['default'] = $parts[$p+1];
                }

                if( preg_match('#auto\s*increment#i',$columnsql) ) {
                    $column['autoIncrement'] = true;
                }

                if( preg_match('#not\s+null#i',$columnsql) ) {
                    $column['notNull'] = true;
                } elseif( preg_match('#null#i',$columnsql) ) {
                    $column['null'] = true;
                }

                $columns[] = (object) $column;
            }
            return $columns;
        } else {
            throw new Exception("Table $table parse error.");
        }
    }

    public function getTableSchema($table) 
    {
        $columns = $this->parseTableSql($table);

        $schema = new SchemaDeclare;
        $schema->columnNames = $schema->columns = array();

        foreach( $columns as $columnAttr ) {
            $type = $columnAttr->type;
            $name = $columnAttr->name;
            $isa = $this->typenameToIsa($type);

            $column = $schema->column($name);
            $column->type( $type );
            if( isset($columnAttr->null) )
                $column->null(true);
            elseif( isset($columnAttr->notNull) )
                $column->notNull(true);

            if( isset($columnAttr->pk) ) {
                $column->primary(true);
                $schema->primaryKey = $name;
            }
            elseif(isset($column->unique)) {
                $column->unique(true);
            }

            if( isset($columnAttr->autoIncrement) ) {
                $column->autoIncrement(true);
            }

            if( isset($columnAttr->default) ) {
                $default = $columnAttr->default;
            }
            if($isa)
                $column->isa($isa);
        }
        return $schema;

    }
}



