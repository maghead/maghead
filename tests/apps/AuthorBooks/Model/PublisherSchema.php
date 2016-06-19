<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class PublisherSchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}


