<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassDeclare;
use ClassTemplate\ClassDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class CollectionClassFactory
{
    public static function create(SchemaDeclare $schema)
    {
        $cTemplate = new ClassDeclare($schema->getCollectionClass());
        $cTemplate->extendClass( '\\' . $schema->getBaseCollectionClass() );
        return $cTemplate;
    }
}
