<?php

namespace Maghead\Schema\Factory;

use ClassTemplate\ClassFile;
use Maghead\Schema\DeclareSchema;

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

        $cTemplate->addStaticMethod('public', 'getSchema', [], function() use ($schema) {
            return [
                "static \$schema;",
                "if (\$schema) {",
                "   return \$schema;",
                "}",
                "return \$schema = new \\{$schema->getSchemaProxyClass()};",
            ];
        });

        // interfaces
        if ($ifs = $schema->getCollectionInterfaces()) {
            foreach ($ifs as $iface) {
                $cTemplate->implementClass($iface);
            }
        }

        return $cTemplate;
    }
}
