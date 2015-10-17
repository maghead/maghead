<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\DeclareSchema;
use DateTime;
use SerializerKit\PhpSerializer;

function php_var_export($obj)
{
    $ser = new PhpSerializer;
    $ser->return = false;
    return $ser->encode( $obj );
}


class SchemaProxyClassFactory
{
    public static function create(DeclareSchema $schema)
    {
        $schemaClass = get_class($schema);
        $schemaArray = $schema->export();

        $cTemplate = new ClassFile($schema->getSchemaProxyClass());
        $cTemplate->extendClass('\\LazyRecord\\Schema\\RuntimeSchema');

        $cTemplate->addConsts(array(
            'schema_class'     => $schemaClass,
            'model_name'       => $schema->getModelName(),
            'model_namespace'  => $schema->getNamespace(),
            'COLLECTION_CLASS' => $schemaArray['collection_class'],
            'MODEL_CLASS'      => $schemaArray['model_class'],
            'PRIMARY_KEY'      => $schemaArray['primary_key'],
            'TABLE' => $schema->getTable(),
            'LABEL' =>  $schema->getLabel(),
        ));

        $cTemplate->useClass('\\LazyRecord\\Schema\\RuntimeColumn');
        $cTemplate->useClass('\\LazyRecord\\Schema\\Relationship');

        $cTemplate->addPublicProperty('columnNames', $schemaArray['column_names']);
        // $cTemplate->addPublicProperty('primaryKey', $schemaArray['primary_key']);
        // $cTemplate->addPublicProperty('table', $schemaArray['table']);
        $cTemplate->addPublicProperty('modelClass', $schemaArray['model_class']);
        $cTemplate->addPublicProperty('collectionClass', $schemaArray['collection_class']);
        $cTemplate->addPublicProperty('label', $schemaArray['label']);
        $cTemplate->addPublicProperty('readSourceId', $schemaArray['read_data_source']);
        $cTemplate->addPublicProperty('writeSourceId', $schemaArray['write_data_source']);
        $cTemplate->addPublicProperty('relations', array());


        $cTemplate->addStaticVar( 'column_names',  $schema->getColumnNames() );
        $cTemplate->addStaticVar( 'column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
        $cTemplate->addStaticVar( 'mixin_classes',  array_reverse($schema->getMixinSchemaClasses()) );
        $cTemplate->addStaticVar( 'column_names_include_virtual',  $schema->getColumnNames(true) );

        $constructor = $cTemplate->addMethod('public', '__construct', []);
        if (!empty($schemaArray['relations'])) {
            $constructor->block[] = '$this->relations = ' . php_var_export($schemaArray['relations']) . ';';
        }


        foreach ($schemaArray['column_data'] as $columnName => $columnAttributes) {
            // $this->columns[ $column->name ] = new RuntimeColumn($column->name, $column->export());
            $constructor->block[] = '$this->columns[ ' . var_export($columnName, true) . ' ] = new RuntimeColumn(' 
                . var_export($columnName, true) . ',' 
                . php_var_export($columnAttributes['attributes']) . ');';
        }
        // $method->block[] = 'parent::__construct();';

        /*
        // export column names including virutal columns
        $cTemplate->addStaticVar( 'column_names_include_virtual',  $schema->getColumnNames(true) );

        // Aggregate basic translations from labels
        $msgIds = $schema->getMsgIds();
        $cTemplate->setMsgIds($msgIds);
        */
        return $cTemplate;
    }
}

