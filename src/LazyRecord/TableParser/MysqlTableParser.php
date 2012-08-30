<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\Schema\SchemaDeclare;

class MysqlTableParser extends BaseTableParser
{
    public function getTables()
    {
        $stm = $this->connection->query('show tables;');
        $rows = $stm->fetchAll( PDO::FETCH_NUM);
        return array_map(function($row) { return $row[0]; },$rows);
    }

    public function getTableSchema($table)
    {
        $stm = $this->connection->query("show columns from $table;");
        $schema = new SchemaDeclare;
        $schema->columnNames = $schema->columns = array();
        $rows = $stm->fetchAll();
        foreach( $rows as $row ) {
            $type = $row['Type'];
            $isa = $this->typenameToIsa($type);

            // reverse type for mysql
            if ( 'int(11)' === $type ) {
                $type = 'integer';
            }
            else if( 'tinyint(1)' === $type ) {
                $type = 'boolean';
                $isa = 'bool';
            }

            $column = $schema->column( $row['Field'] );
            $column->type( $type );
            $column->null( $row['Null'] === 'YES' );

            if( 'PRI' === $row['Key'] ) {
                $column->primary(true);
                $schema->primaryKey = $row['Field'];
            }
            elseif( 'UNI' === $row['Key'] ) {
                $column->unique(true);
            }

            if($isa) {
                $column->isa($isa);
            }

            if( NULL !== $row['Default'] ) {
                // $column->default( array($row['Default']) );
            }
        }
        return $schema;
    }


}
