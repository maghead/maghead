<?php

namespace Maghead\Schema;

use Maghead\ClassUtils;

/**
 * Schema loader actually catches 
 * schema object instances by schema class name.
 */
class SchemaLoader
{
    public static $schemas = array();

    /**
     * Load or create schema object and cache it.
     *
     * @param string $class Schema class naem
     *
     * @return Maghead\Schema\RuntimeSchema
     */
    public static function load($class)
    {
        if (isset(self::$schemas[$class])) {
            return self::$schemas[$class];
        }
        if (class_exists($class, true)) {
            return self::$schemas[ $class ] = new $class();
        }
    }

    /**
     * @return DeclareSchema[] Return declared schema object in associative array
     */
    public static function loadSchemaTableMap()
    {
        $schemas = self::findDeclaredSchemas();

        return SchemaUtils::buildSchemaMap($schemas);
    }

    /**
     * Returns declared schema objects.
     *
     * @return array Schema objects
     */
    public static function findDeclaredSchemas()
    {
        return SchemaUtils::expandSchemaClasses(
            ClassUtils::get_declared_schema_classes()
        );
    }
}
