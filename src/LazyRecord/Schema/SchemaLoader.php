<?php
namespace LazyRecord\Schema;

class SchemaLoader
{
    static $schemas = array();

    static function load($class)
    {
        return ( isset($schemas[ $class ] ) ) 
                ? $schemas[ $class ] 
                : $schemas[ $class ] = new $class;
    }

}




