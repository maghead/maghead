<?php
namespace LazyRecord\SqlBuilder;
use Exception;

class SqlBuilder
{
    static function create($driver,$options = array() ) 
    {
        // Get driver type
        $type = $driver->type;
        if( ! $type ) {
            throw new Exception("Driver type is not defined.");
        }
        $class = 'LazyRecord\SqlBuilder\\' . ucfirst($type) . 'Builder';
        return new $class($driver,$options);
    }
}

