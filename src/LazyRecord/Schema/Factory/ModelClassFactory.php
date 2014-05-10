<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;

class ModelClassFactory
{
    public static function create($schema) {
        $cTemplate = new ClassTemplate( $schema->getModelClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( '\\' . $schema->getBaseModelClass() );
        return $cTemplate;
    }
}


