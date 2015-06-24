<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\DeclareSchema;

class BaseCollectionClassFactory
{
    public static function create(DeclareSchema $schema, $baseCollectionClass)
    {
        $cTemplate = new ClassFile($schema->getBaseCollectionClass());
        $cTemplate->addConsts(array(
            'schema_proxy_class' => $schema->getSchemaProxyClass(),
            'model_class'        => $schema->getModelClass(),
            'table'              => $schema->getTable(),
            'read_source_id'     => $schema->getReadSourceId(),
            'write_source_id'     => $schema->getWriteSourceId(),
        ));
        if ($traitClasses = $schema->getCollectionTraitClasses()) {
            foreach($traitClasses as $traitClass) {
                $cTemplate->useTrait($traitClass);
            }
        }
        $cTemplate->extendClass( '\\' . $baseCollectionClass );

        // interfaces
        if ($ifs = $schema->getModelInterfaceClasses()) {
            foreach ($ifs as $iface) {
                $cTemplate->implementClass($iface);
            }
        }

        return $cTemplate;
    }
}

