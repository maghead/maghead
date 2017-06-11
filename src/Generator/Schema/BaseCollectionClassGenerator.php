<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use Maghead\Schema\DeclareSchema;
use Maghead\Generator\GetSchemaMethodGenerator;

class BaseCollectionClassGenerator
{
    public static function create(DeclareSchema $schema, $baseCollectionClass)
    {
        $cTemplate = clone $schema->classes->baseCollection;
        $cTemplate->addConsts(array(
            'SCHEMA_PROXY_CLASS' => $schema->getSchemaProxyClass(),
            'MODEL_CLASS' => $schema->getModelClass(),
            'TABLE' => $schema->getTable(),
            'READ_SOURCE_ID' => $schema->getReadSourceId(),
            'WRITE_SOURCE_ID' => $schema->getWriteSourceId(),
            'PRIMARY_KEY' => $schema->primaryKey,
        ));
        $cTemplate->extendClass('\\'.$baseCollectionClass);

        $cTemplate->addStaticMethod('public', 'createRepo', ['$write', '$read'], function () use ($schema) {
            return "return new \\{$schema->getBaseRepoClass()}(\$write, \$read);";
        });

        GetSchemaMethodGenerator::generate($cTemplate, $schema);

        return $cTemplate;
    }
}
