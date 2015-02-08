<?php
namespace LazyRecord\Model;
use LazyRecord\Schema\SchemaDeclare;

class MetadataSchema extends SchemaDeclare 
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

