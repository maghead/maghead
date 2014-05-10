<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;

class BaseCollectionClassFactory
{
    public static function create($schema, $baseCollectionClass)
    {
        $cTemplate = new ClassTemplate($schema->getBaseCollectionClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->addConst( 'schema_proxy_class' , $schema->getSchemaProxyClass() );
        $cTemplate->addConst( 'model_class' , $schema->getModelClass() );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->extendClass( '\\' . $baseCollectionClass );
        return $cTemplate;
    }
}

