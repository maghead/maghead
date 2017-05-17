<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use Maghead\Schema\DeclareSchema;

class ModelClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $cTemplate = new ClassFile($schema->getModelClass());
        $cTemplate->extendClass('\\'.$schema->getBaseModelClass());

        return $cTemplate;
    }
}
