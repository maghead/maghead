<?php
namespace LazyRecord\Schema;


/**
 * Schema loader actually catches 
 * schema object instances by schema class name.
 */
class SchemaLoader
{
    static $schemas = array();


    /**
     * Load or create schema object.
     *
     * @param string $class Schema class naem
     *
     * @return LazyRecord\Schema\RuntimeSchema
     */
    static function load($class)
    {
        return ( isset($schemas[ $class ] ) ) 
                ? $schemas[ $class ] 
                : $schemas[ $class ] = new $class;
    }
}

