<?php

namespace LazyRecord\Schema;

use LazyRecord\ClassUtils;
use LazyRecord\ConfigLoader;

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
     * @return LazyRecord\Schema\RuntimeSchema
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
    public static function loadSchemaTableMap(ConfigLoader $config = null)
    {
        if (!$config) {
            $container = ServiceContainer::getInstance();
            $config = $container['config_loader'];
        }

        // pre-initialize all schema objects and expand template schema
        $schemas = SchemaUtils::findSchemasByConfigLoader($config);
        $schemas = SchemaUtils::filterBuildableSchemas($schemas);
        return SchemaUtils::buildSchemaMap($schemas);
    }

    /**
     * Returns declared schema objects.
     *
     * @return array Schema objects
     */
    public static function loadDeclaredSchemas()
    {
        return SchemaUtils::expandSchemaClasses(
            ClassUtils::get_declared_schema_classes()
        );
    }
}
