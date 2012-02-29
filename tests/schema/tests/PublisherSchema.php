<?php
namespace tests;
use Lazy\Schema\SchemaDeclare;
use Lazy\Schema;

class PublisherSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('id')
            ->type('integer')
            ->primary()
            ->autoIncrement();
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}


