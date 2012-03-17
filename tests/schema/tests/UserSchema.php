<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema;

class UserSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('account')
            ->isa('str')
            ->unique()
            ->type('varchar(128)');

        $this->column('password')
            ->isa('str')
            ->varchar(256);
    }

}
