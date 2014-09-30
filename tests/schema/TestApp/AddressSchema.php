<?php
namespace TestApp;
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
        $this->belongsTo( 'author', '\\TestApp\\AuthorSchema', 'id' , 'author_id' );
    }
}
