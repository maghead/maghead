<?php
namespace tests;
use Lazy\Schema\SchemaDeclare;
use Lazy\Schema;

class PublisherSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}


