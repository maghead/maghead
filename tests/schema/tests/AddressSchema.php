<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema;

class AddressSchema extends SchemaDeclare
{
    function schema()
    {
        $this->column('author_id')
                ->integer();

        $this->column('address')
                ->varchar(128);


        /**
         * $address->author 
         */
        $this->belongsTo( 'author', 'author_id' , '\tests\AuthorSchema', 'id' );
    }
}
