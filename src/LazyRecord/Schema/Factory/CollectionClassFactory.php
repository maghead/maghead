<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;

class CollectionClassFactory
{
    public static function create($schema)
    {
        $cTemplate = new ClassTemplate($schema->getCollectionClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( '\\' . $schema->getBaseCollectionClass() );
        return $cTemplate;
    }
}
