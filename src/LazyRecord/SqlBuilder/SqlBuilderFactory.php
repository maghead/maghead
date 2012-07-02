<?php
namespace LazyRecord\SqlBuilder;
use Exception;

class SqlBuilderFactory
{
    static function create($driver,$options = array() ) 
    {
        // Get driver type
        $type = $driver->type;
        if( ! $type ) {
            throw new Exception("Driver type is not defined.");
        }
        $class = get_class($this) . '\\' . ucfirst($type) . 'Builder';
        $builder = new $class($this);
        return $builder;
    }
}



