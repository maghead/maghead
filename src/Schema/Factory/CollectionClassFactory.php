<?php

namespace LazyRecord\Schema\Factory;

use ClassTemplate\ClassFile;
use LazyRecord\Schema\DeclareSchema;

class CollectionClassFactory
{
    public static function create(DeclareSchema $schema)
    {
        $cTemplate = new ClassFile($schema->getCollectionClass());
        $cTemplate->extendClass('\\'.$schema->getBaseCollectionClass());

        return $cTemplate;
    }
}
