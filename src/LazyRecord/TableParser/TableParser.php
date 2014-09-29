<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\TableParser\MysqlTableParser;
use LazyRecord\TableParser\PgsqlTableParser;
use LazyRecord\TableParser\SqliteTableParser;
use SQLBuilder\Driver;

class TableParser
{
    static function create(Driver $driver,PDO $connection) 
    {
        $class = 'LazyRecord\\TableParser\\' . ucfirst($driver->type) . 'TableParser';
        if( class_exists($class,true) ) {
            $parser = new $class($driver,$connection);
            return $parser;
        } else {
            throw new Exception("parser driver does not support {$driver->type} currently.");
        }
    }
}

