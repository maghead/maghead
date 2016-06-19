<?php

namespace LazyRecord\TableParser;

use PDO;
use Exception;
use LazyRecord\Connection;
use LazyRecord\ConfigLoader;
use SQLBuilder\Driver\BaseDriver;

class TableParser
{
    public static function create(Connection $connection, BaseDriver $driver)
    {
        $class = 'LazyRecord\\TableParser\\'.ucfirst($driver->getDriverName()).'TableParser';
        if (class_exists($class, true)) {
            return new $class($connection, $driver);
        } else {
            throw new Exception("parser driver does not support {$driver->getDriverName()} currently.");
        }
    }
}
