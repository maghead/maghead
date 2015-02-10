<?php
namespace TestApp\Model;
use LazyRecord\Schema;

class AddressSchema extends Schema
{
    function schema()
    {
        $this->column('author_id')
                ->integer();

        $this->column('address')
                ->varchar(128);

        $this->column('foo')
                ->varchar(128);

        /**
         * $address->author 
         */
        $this->belongsTo( 'author', '\\TestApp\Model\\AuthorSchema', 'id' , 'author_id' );
    }
}
