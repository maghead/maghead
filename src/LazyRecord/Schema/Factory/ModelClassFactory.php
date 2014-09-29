<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class ModelClassFactory
{
    public static function create(SchemaDeclare $schema) {
        $cTemplate = new ClassTemplate( $schema->getModelClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( '\\' . $schema->getBaseModelClass() );
        return $cTemplate;
    }
}


