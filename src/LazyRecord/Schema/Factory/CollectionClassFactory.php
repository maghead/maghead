<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class CollectionClassFactory
{
    public static function create(SchemaDeclare $schema)
    {
        $cTemplate = new ClassFile($schema->getCollectionClass());
        $cTemplate->extendClass( '\\' . $schema->getBaseCollectionClass() );
        return $cTemplate;
    }
}
