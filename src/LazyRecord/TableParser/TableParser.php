<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;
use LazyRecord\TableParser\MysqlTableParser;
use LazyRecord\TableParser\PgsqlTableParser;
use LazyRecord\TableParser\SqliteTableParser;
use SQLBuilder\Driver\BaseDriver;

class TableParser
{
    static function create(BaseDriver $driver,PDO $connection) 
    {
        $class = 'LazyRecord\\TableParser\\' . ucfirst($driver->getDriverName()) . 'TableParser';
        if (class_exists($class,true) ) {
            return new $class($driver,$connection);
        } else {
            throw new Exception("parser driver does not support {$driver->getDriverName()} currently.");
        }
    }
}

