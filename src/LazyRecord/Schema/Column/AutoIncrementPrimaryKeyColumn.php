<?php
namespace LazyRecord\Schema\Column;
use LazyRecord\Schema\DeclareColumn;

class AutoIncrementPrimaryKeyColumn extends DeclareColumn
{
    public function __construct($name = 'id', $type = 'int')
    {
        parent::__construct($name);
        $this->type($type)
            ->isa('int')
            ->notNull()
            ->unsigned()
            ->primary()
            ->autoIncrement();
    }
}



