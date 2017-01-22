<?php

namespace Maghead\Schema\Column;

use Maghead\Schema\DeclareColumn;
use Maghead\Schema\DeclareSchema;

class AutoIncrementPrimaryKeyColumn extends DeclareColumn
{
    public function __construct(DeclareSchema $schema, $name = 'id', $type = 'integer')
    {
        parent::__construct($schema, $name);
        $this->type($type)
            ->isa('int')
            ->notNull()
            ->unsigned()
            ->primary()
            ->autoIncrement()
            ->renderAs('HiddenInput')
            ;
    }
}
