<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class AddressSchema extends Schema
{
    public function schema()
    {
        $this->column('author_id')
            ->unsigned()
            ->integer();

        $this->column('address')
                ->varchar(128);

        $this->column('unused')
            ->boolean()
            ->defaultValue(false)
            ;

        $this->belongsTo('author', '\\AuthorBooks\Model\\AuthorSchema')
            ->by('author_id')
            ->onDelete('CASCADE');
    }
}
