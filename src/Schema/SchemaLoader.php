<?php

namespace Maghead\Schema;


/**
 * Schema loader actually catches
 * schema object instances by schema class name.
 */
class SchemaLoader
{
    protected static $objects = [];

    public static function load($class)
    {
        return self::$objects[$class] = new $class;
    }

    public static function attach(Schema $schema)
    {
        self::$objects[get_class($schema)] = $schema;
    }

    /**
     * @return DeclareSchema[] Return declared schema object in associative array
     */
    public static function loadSchemaTableMap()
    {
        $array = self::loadDeclaredSchemas();
        return SchemaCollection::create($array)->tables()->getArrayCopy();
    }

    /**
     * Returns declared schema objects.
     *
     * @return array Schema objects
     */
    public static function loadDeclaredSchemas()
    {
        $collection = SchemaCollection::declared()->buildable();
        return SchemaUtils::expandSchemas($collection);
    }
}
