<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use SQLBuilder\Driver;

abstract class BaseTableParser
{
    public $driver;
    public $connection;

    public function __construct($driver, PDO $connection)
    {
        $this->driver = $driver;
        $this->connection = $connection;
    }

    abstract function getTables();
    abstract function getTableSchema($table);

    public function getTableSchemas()
    {
        $tableSchemas = array();
        $tables = $this->getTables();
        foreach(  $tables as $table ) {
            $tableSchemas[ $table ] = $this->getTableSchema( $table );
        }
        return $tableSchemas;
    }

    public function typenameToIsa($type)
    {
        $type = strtolower($type);
        if( preg_match( '/^(char|varchar|text)/' , $type ) ) {
            return 'str';
        }
        elseif( preg_match('/^(int|tinyint|smallint|mediumint|bigint)/', $type ) ) {
            return 'int';
        }
        elseif( 'boolean' === $type || 'bool' === $type ) {
            return 'bool';
        }
        elseif( 'blob' === $type || 'binary' === $type ) {
            return 'str';
        }
        elseif( 'double' === $type ) {
            return 'double';
        }
        elseif( 'float' === $type ) {
            return 'float';
        }
        elseif( 'datetime' === $type || 'date' === $type ) {
            return 'DateTime';
        }
        elseif( preg_match('/timestamp/', $type ) ) {
            return 'DateTime';
        }
        elseif( 'time' == $type ) {
            // DateTime::createFromFormat('H:s','10:00')
            return 'DateTime';
        }
        else {
            throw new Exception("Unknown type $type");
        }
    }

}



