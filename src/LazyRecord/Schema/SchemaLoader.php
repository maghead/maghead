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
     * Load or create schema object and cache it.
     *
     * @param string $class Schema class naem
     *
     * @return LazyRecord\Schema\RuntimeSchema
     */
    static function load($class)
    {
        if (isset( self::$schemas[ $class ] )) {
            return self::$schemas[ $class ];
        }
        if (class_exists($class,true)) {
            return self::$schemas[ $class ] = new $class;
        }
    }
}

