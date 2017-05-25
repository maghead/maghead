<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use Maghead\Schema\DeclareSchema;

class ModelClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $template = clone $schema->classes->model;
        $template->extendClass('\\'.$schema->getBaseModelClass());

        return $template;
    }
}
