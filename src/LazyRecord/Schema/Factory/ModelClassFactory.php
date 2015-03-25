<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class ModelClassFactory
{
    public static function create(SchemaDeclare $schema) {
        $cTemplate = new TemplateClassDeclare( $schema->getModelClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( '\\' . $schema->getBaseModelClass() );
        return $cTemplate;
    }
}


