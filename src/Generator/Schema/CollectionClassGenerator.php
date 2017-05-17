<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
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
