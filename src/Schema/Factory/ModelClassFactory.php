<?php

namespace LazyRecord\Schema\Factory;

use ClassTemplate\ClassFile;
use LazyRecord\Schema\DeclareSchema;

class ModelClassFactory
{
    public static function create(DeclareSchema $schema)
    {
        $cTemplate = new ClassFile($schema->getModelClass());
        $cTemplate->extendClass('\\'.$schema->getBaseModelClass());

        return $cTemplate;
    }
}
