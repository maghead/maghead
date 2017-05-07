<?php

namespace Maghead\Schema;

use Maghead\Utils\ClassUtils;

/**
 * Schema loader actually catches
 * schema object instances by schema class name.
 */
class SchemaLoader
{
    /**
     * @return DeclareSchema[] Return declared schema object in associative array
     */
    public static function loadSchemaTableMap()
    {
        $schemas = self::loadDeclaredSchemas();

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
            SchemaUtils::getLoadedDeclareSchemaClasses()
        );
    }
}
