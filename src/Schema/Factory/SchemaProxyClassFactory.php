<?php

namespace Maghead\Schema\Factory;

use ClassTemplate\ClassFile;
use Maghead\Schema\DeclareSchema;
use SerializerKit\PhpSerializer;

function php_var_export($obj)
{
    $ser = new PhpSerializer();
    $ser->return = false;

    return $ser->encode($obj);
}

class SchemaProxyClassFactory
{
    public static function create(DeclareSchema $schema)
    {
        $schemaClass = get_class($schema);
        $schemaArray = $schema->export();

        $cTemplate = new ClassFile($schema->getSchemaProxyClass());
        $cTemplate->extendClass('\\Maghead\\Schema\\RuntimeSchema');

        $cTemplate->addConsts(array(
            'schema_class' => $schemaClass,
            'model_name' => $schema->getModelName(),
            'model_namespace' => $schema->getNamespace(),
            'COLLECTION_CLASS' => $schemaArray['collection_class'],
            'MODEL_CLASS' => $schemaArray['model_class'],
            'PRIMARY_KEY' => $schema->getPrimaryKey(),
            'TABLE' => $schema->getTable(),
            'LABEL' => $schema->getLabel(),
        ));

        $cTemplate->useClass('\\Maghead\\Schema\\RuntimeColumn');
        $cTemplate->useClass('\\Maghead\\Schema\\Relationship\\Relationship');

        $cTemplate->addPublicProperty('columnNames', $schema->getColumnNames());
        $cTemplate->addPublicProperty('primaryKey', $schema->getPrimaryKey());
        $cTemplate->addPublicProperty('columnNamesIncludeVirtual', $schema->getColumnNames(true));
        $cTemplate->addPublicProperty('label', $schemaArray['label']);
        $cTemplate->addPublicProperty('readSourceId', $schemaArray['read_data_source']);
        $cTemplate->addPublicProperty('writeSourceId', $schemaArray['write_data_source']);
        $cTemplate->addPublicProperty('relations', array());

        $cTemplate->addStaticVar('column_hash',  array_fill_keys($schema->getColumnNames(), 1));
        $cTemplate->addStaticVar('mixin_classes',  array_reverse($schema->getMixinSchemaClasses()));

        $constructor = $cTemplate->addMethod('public', '__construct', []);
        if (!empty($schemaArray['relations'])) {
            $constructor->block[] = '$this->relations = '.php_var_export($schemaArray['relations']).';';
        }

        foreach ($schemaArray['column_data'] as $columnName => $columnAttributes) {
            // $this->columns[ $column->name ] = new RuntimeColumn($column->name, $column->export());
            $constructor->block[] = '$this->columns[ '.var_export($columnName, true).' ] = new RuntimeColumn('
                .var_export($columnName, true).','
                .php_var_export($columnAttributes['attributes']).');';
        }
        // $method->block[] = 'parent::__construct();';

        /*
        // export column names including virutal columns
        // Aggregate basic translations from labels
        $msgIds = $schema->getMsgIds();
        $cTemplate->setMsgIds($msgIds);
        */
        return $cTemplate;
    }
}
