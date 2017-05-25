<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use Maghead\Schema\DeclareSchema;

class RepoClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $template = clone $schema->classes->repo;
        $template->extendClass('\\'.$schema->getBaseRepoClass());

        return $template;
    }
}
