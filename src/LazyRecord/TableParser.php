<?php
namespace LazyRecord;
use PDO;
use Exception;

abstract class BaseTablePaser
{
    public $driver;
    public $connection;

    public function __construct($driver,$connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    abstract function getTables();

}

class PgsqlTableParser extends BaseTablePaser
{

    public function getTables()
    {
        $stm = $this->connection->query('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\';');
        $rows = $stm->fetchAll( PDO::FETCH_NUM);
        return array_map(function($row) { return $row[0]; },$rows);
    }

    public function getTableSchema($table)
    {
        $sql = "SELECT * FROM information_schema.columns WHERE table_name = '$table';";
        $stm = $this->connection->query($sql);
        $schema = new Schema\SchemaDeclare;
        $schema->columnNames = $schema->columns = array();
        $rows = $stm->fetchAll( PDO::FETCH_OBJ );

        /**
         * more detailed attributes
         *
         * > select * from pg_attribute, pg_type where typname = 'addresses';
         * > select * from pg_attribute, pg_type where typname = 'addresses' and attname not in ('cmin','cmax','ctid','oid','tableoid','xmin','xmax');
         *
         * > SELECT
         *      a.attname as "Column",
         *      pg_catalog.format_type(a.atttypid, a.atttypmod) as "Datatype"
         *  FROM
         *      pg_catalog.pg_attribute a
         *  WHERE
         *      a.attnum > 0
         *      AND NOT a.attisdropped
         *      AND a.attrelid = (
         *          SELECT c.oid
         *          FROM pg_catalog.pg_class c
         *              LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
         *          WHERE c.relname ~ '^(books)$'  
         *              AND pg_catalog.pg_table_is_visible(c.oid)
         *      )
         *  ;
         *
         * @see http://notfaq.wordpress.com/2006/07/29/sql-postgresql-get-tables-and-columns/
         */
        foreach( $rows as $row ) {
            $column = $schema->column( $row->column_name );
            $column->null( $row->is_nullable === 'YES' );

            $type = $row->data_type;
            if( $type === 'character varying' )
                $type = 'varchar(' . $row->character_maximum_length . ')' ;

            $column->type( $type );
            // $row->ordinal_position
            // $row->data_type
            // $row->character_maximum_length
        }
        return $schema;
    }
}

class MysqlTableParser extends BaseTablePaser
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
        $schema = new Schema\SchemaDeclare;
        $schema->columnNames = $schema->columns = array();
        $rows = $stm->fetchAll();
        foreach( $rows as $row ) {
            $type = $row['Type'];
            $isa = $this->_parserType($type);

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


}

class TableParser
{
    static function create($driver,$connection) 
    {
        if( $driver->type === 'mysql' ) {
            $parser = new MysqlTableParser($driver,$connection);
            return $parser;
        }
        elseif( $driver->type === 'pgsql' ) {
            $parser = new PgsqlTableParser($driver,$connection);
            return $parser;
        }
        else {
            throw new Exception("Driver {$driver->type} is not supported.");
        }

    }
}



