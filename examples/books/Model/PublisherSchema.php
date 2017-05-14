<?php
namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;

class PublisherSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }
}
