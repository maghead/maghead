<?php
namespace TestApp\Model;
use LazyRecord\Schema\DeclareSchema;

class IDNumberSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('id_number')
            ->varchar(10)
            ->validator('TW\\IDNumberValidator');
    }
}



