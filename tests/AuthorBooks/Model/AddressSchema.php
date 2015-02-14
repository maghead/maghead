<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class AddressSchema extends Schema
{
    public function schema()
    {
        $this->column('author_id')
                ->integer();

        $this->column('address')
                ->varchar(128);

        $this->column('unused')
            ->boolean()
            ->defaultValue(false)
            ;
        /**
         * $address->author 
         */
        $this->belongsTo( 'author', '\\AuthorBooks\Model\\AuthorSchema', 'id' , 'author_id' );
    }
}
