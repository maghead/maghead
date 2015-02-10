<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class PublisherSchema extends Schema
{

    function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}


