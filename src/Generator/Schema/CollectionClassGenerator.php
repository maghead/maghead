<?php

namespace Maghead\Schema\Factory;

use ClassTemplate\ClassFile;
use Maghead\Schema\DeclareSchema;

class CollectionClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $cTemplate = new ClassFile($schema->getCollectionClass());
        $cTemplate->extendClass('\\'.$schema->getBaseCollectionClass());

        return $cTemplate;
    }
}
