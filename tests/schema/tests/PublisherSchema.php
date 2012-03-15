<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema;

class PublisherSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}


