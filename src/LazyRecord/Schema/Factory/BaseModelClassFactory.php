<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;
use Doctrine\Common\Inflector\Inflector;

class BaseModelClassFactory
{
    public static function create(SchemaDeclare $schema, $baseClass) {
        $cTemplate = new ClassTemplate( $schema->getBaseModelClass() , array(
            'template' => 'Class.php.twig',
        ));
        $cTemplate->addConsts(array(
            'schema_proxy_class' => $schema->getSchemaProxyClass(),
            'collection_class'   => $schema->getCollectionClass(),
            'model_class'        => $schema->getModelClass(),
            'table'              => $schema->getTable(),
            'read_source_id'     => $schema->getReadSourceId(),
            'write_source_id'    => $schema->getWriteSourceId(),
            'primary_key'        => $schema->primaryKey,
        ));

        $cTemplate->addMethod('public', 'getSchema', [], [
            'if ($this->_schema) {',
            '   return $this->_schema;',
            '}',
            'return $this->_schema = \LazyRecord\Schema\SchemaLoader::load(' . var_export($schema->getSchemaProxyClass(),true) .  ');',
        ]);

        $cTemplate->addStaticVar('column_names',  $schema->getColumnNames());
        $cTemplate->addStaticVar('column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
        $cTemplate->addStaticVar('mixin_classes', array_reverse($schema->getMixinSchemaClasses()) );

        if ($traitClasses = $schema->getModelTraitClasses()) {
            foreach($traitClasses as $traitClass) {
                $cTemplate->useTrait($traitClass);
            }
        }

        $cTemplate->extendClass( '\\' . $baseClass );

        // Create column accessor
        if ($schema->enableColumnAccessors) {
            foreach ($schema->getColumnNames() as $columnName) {
                $accessorMethodName = 'get' . ucfirst(Inflector::camelize($columnName));
                $cTemplate->addMethod('public', $accessorMethodName, [], [
                    'if (isset($this->_data[' . var_export($columnName, true) . '])) {',
                    '    return $this->_data[' . var_export($columnName, true) . '];',
                    '}',
                ]);
            }
        }


        return $cTemplate;
    }
}

