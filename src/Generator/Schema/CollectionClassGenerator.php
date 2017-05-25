<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use Maghead\Schema\DeclareSchema;

class CollectionClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $template = clone $schema->classes->collection;
        $template->extendClass('\\'.$schema->getBaseCollectionClass());

        return $template;
    }
}
