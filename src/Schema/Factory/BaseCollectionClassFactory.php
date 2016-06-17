<?php

namespace LazyRecord\Schema\Factory;

use ClassTemplate\ClassFile;
use LazyRecord\Schema\DeclareSchema;

class BaseCollectionClassFactory
{
    public static function create(DeclareSchema $schema, $baseCollectionClass)
    {
        $cTemplate = new ClassFile($schema->getBaseCollectionClass());
        $cTemplate->addConsts(array(
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'MODEL_CLASS' => $schema->getModelClass(),
            'TABLE' => $schema->getTable(),
            'READ_SOURCE_ID' => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID' => $schema->getWriteSourceId(),
            'PRIMARY_KEY' => $schema->primaryKey,
        ));
        if ($traitClasses = $schema->getCollectionTraitClasses()) {
            foreach ($traitClasses as $traitClass) {
                $cTemplate->useTrait($traitClass);
            }
        }
        $cTemplate->extendClass('\\'.$baseCollectionClass);

        // interfaces
        if ($ifs = $schema->getCollectionInterfaces()) {
            foreach ($ifs as $iface) {
                $cTemplate->implementClass($iface);
            }
        }

        return $cTemplate;
    }
}
