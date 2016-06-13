<?php
namespace LazyRecord\Model;
use LazyRecord\Schema\DeclareSchema;

class MetadataSchema extends DeclareSchema 
{
    public function schema() 
    {
        $this->table('__meta__');
        $this->column('id')
            ->integer()
            ->primary()
            ->autoIncrement()
            ;
        $this->column('name')
            ->varchar(128);
        $this->column('value')
            ->varchar(256);

        $this->disableColumnAccessors();
    }
}

