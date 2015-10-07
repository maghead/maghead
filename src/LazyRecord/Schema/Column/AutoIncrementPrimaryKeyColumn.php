<?php
namespace LazyRecord\Schema\Column;
use LazyRecord\Schema\ColumnDeclare;

class AutoIncrementPrimaryKeyColumn extends ColumnDeclare
{
    public function __construct()
    {
        parent::__construct('id');
        $this->isa('int')
            ->integer()
            ->notNull()
            ->primary()
            ->autoIncrement();
    }
}



