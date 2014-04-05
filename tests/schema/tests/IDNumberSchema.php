<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;

class IDNumberSchema extends SchemaDeclare
{
    public function schema()
    {
        $this->column('id_number')
            ->varchar(10)
            ->validator('TW\\IDNumberValidator');
    }
}



