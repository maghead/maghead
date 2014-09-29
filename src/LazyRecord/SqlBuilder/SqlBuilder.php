<?php
namespace LazyRecord\SqlBuilder;
use Exception;
use RuntimeException;
use LazyRecord\QueryDriver;

class SqlBuilder
{
    static function create(QueryDriver $driver,$options = array() ) 
    {
        // Get driver type
        $type = $driver->type;
        if (! $type ) {
            throw new RuntimeException("Driver type is not defined.");
        }
        $class = 'LazyRecord\\SqlBuilder\\' . ucfirst($type) . 'Builder';
        return new $class($driver,$options);
    }
}

