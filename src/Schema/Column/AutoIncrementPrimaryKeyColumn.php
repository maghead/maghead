<?php

namespace LazyRecord\Schema\Column;

use LazyRecord\Schema\DeclareColumn;
use LazyRecord\Schema\DeclareSchema;

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
