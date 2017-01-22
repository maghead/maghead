<?php
namespace AuthorBooks\Model;
use Maghead\Schema;

class PublisherSchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}


