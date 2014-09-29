<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class CollectionClassFactory
{
    public static function create(SchemaDeclare $schema)
    {
        $cTemplate = new ClassTemplate($schema->getCollectionClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( '\\' . $schema->getBaseCollectionClass() );
        return $cTemplate;
    }
}
