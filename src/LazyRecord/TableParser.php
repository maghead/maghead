<?php
namespace LazyRecord;
use Exception;

class MysqlTableParser
{
    public $driver;
    public $connection;

    public function __construct($driver,$connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    public function getTables()
    {
        $stm = $this->connection->query('show tables;');
        $rows = $stm->fetchAll();
        return array_map(function($row) { return $row[0]; },$rows);
    }

    public function _parserType($type)
    {
        $type = strtolower($type);
        if( preg_match( '/^(char|varchar|text)/' , $type ) ) {
            return 'str';
        }
        elseif( preg_match('/^(int|tinyint|smallint|mediumint|bigint)/', $type ) ) {
            return 'int';
        }
        elseif( 'date' === $type ) {
            return 'DateTime';
        }
        elseif( preg_match('/timestamp/', $type ) ) {
            return 'DateTime';
        }
        else {
            throw new Exception("Unknown type $type");
        }
    }

    public function getTableSchema($table)
    {
        $stm = $this->connection->query("show columns from $table;");
        $schema = new Schema\SchemaDeclare;
        $schema->columnNames = $schema->columns = array();
        $rows = $stm->fetchAll();
        foreach( $rows as $row ) {
            $column = $schema->column( $row['Field'] );
            $column->type( $row['Type'] );
            $column->null( $row['Null'] === 'YES' );

            if( 'PRI' === $row['Key'] ) {
                $column->primary(true);
                $schema->primaryKey = $row['Field'];
            }
            elseif( 'UNI' === $row['Key'] ) {
                $column->unique(true);
            }

            if( $isa = $this->_parserType($row['Type']) ) {
                $column->isa($isa);
            }

            if( NULL !== $row['Default'] ) {
                // $column->default( array($row['Default']) );
            }
        }
        return $schema;
    }
}

class TableParser
{
    static function create($driver,$connection) 
    {
        $parser = new MysqlTableParser($driver,$connection);
        return $parser;
    }
}



