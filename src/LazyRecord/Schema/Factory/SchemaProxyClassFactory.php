<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassDeclare;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;
use DateTime;

class SchemaProxyClassFactory
{
    public static function create(SchemaDeclare $schema) {
        $schemaProxyClass = $schema->getSchemaProxyClass();
        $cTemplate = new TemplateClassDeclare( $schemaProxyClass, array( 
            'template_dirs' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Templates',
            'template'      => 'Schema.php.twig',
        ));


        $schemaClass = get_class($schema);
        $schemaArray = $schema->export();
        $cTemplate->addConst( 'schema_class'     , $schemaClass );
        $cTemplate->addConst( 'collection_class' , $schemaArray['collection_class'] );
        $cTemplate->addConst( 'model_class'      , $schemaArray['model_class'] );
        $cTemplate->addConst( 'model_name'       , $schema->getModelName() );
        $cTemplate->addConst( 'model_namespace'  , $schema->getNamespace() );
        $cTemplate->addConst( 'primary_key'      , $schemaArray['primary_key'] );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->addConst( 'label',  $schema->getLabel() );

        // export column names excluding virtual columns
        $cTemplate->addStaticVar( 'column_names',  $schema->getColumnNames() );
        $cTemplate->addStaticVar( 'column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
        $cTemplate->addStaticVar( 'mixin_classes',  array_reverse($schema->getMixinSchemaClasses()) );

        // export column names including virutal columns
        $cTemplate->addStaticVar( 'column_names_include_virtual',  $schema->getColumnNames(true) );
        $cTemplate->schema = $schema;
        $cTemplate->schema_data = $schemaArray;
        $cTemplate->now = new DateTime;

        // Aggregate basic translations from labels
        $msgIds = $schema->getMsgIds();
        $cTemplate->setMsgIds($msgIds);

        return $cTemplate;
    }
}

