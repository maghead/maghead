<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\SchemaUtils;
use LazyRecord\ClassUtils;


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
    static public function load($class)
    {
        if (isset( self::$schemas[$class] )) {
            return self::$schemas[$class];
        }
        if (class_exists($class,true)) {
            return self::$schemas[ $class ] = new $class;
        }
    }


    /**
     * Returns declared schema objects
     *
     * @return array Schema objects
     */
    static public function loadDeclaredSchemas()
    {
        return SchemaUtils::expandSchemaClasses(
            ClassUtils::get_declared_schema_classes()
        );
    }

}

