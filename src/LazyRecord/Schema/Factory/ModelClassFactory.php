<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassDeclare;
use ClassTemplate\ClassDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class ModelClassFactory
{
    public static function create(SchemaDeclare $schema) {
        $cTemplate = new ClassDeclare($schema->getModelClass());
        $cTemplate->extendClass( '\\' . $schema->getBaseModelClass() );
        return $cTemplate;
    }
}


