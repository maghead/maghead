<?php

namespace Maghead\Generator\Schema;

use ClassTemplate\ClassFile;
use Maghead\Schema\DeclareSchema;

class RepoClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $cTemplate = new ClassFile($schema->getRepoClass());
        $cTemplate->extendClass('\\'.$schema->getBaseRepoClass());

        return $cTemplate;
    }
}
