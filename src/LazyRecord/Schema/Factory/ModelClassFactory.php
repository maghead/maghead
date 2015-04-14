<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class ModelClassFactory
{
    public static function create(SchemaDeclare $schema) {
        $cTemplate = new ClassFile($schema->getModelClass());
        $cTemplate->extendClass( '\\' . $schema->getBaseModelClass() );
        return $cTemplate;
    }
}


