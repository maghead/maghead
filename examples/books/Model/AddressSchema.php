<?php
namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;

class AddressSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('author_id')
            ->unsigned()
            ->integer();

        $this->column('address')
            ->varchar(128)
            ;

        $this->column('unused')
            ->boolean()
            ->defaultValue(false)
            ;

        $this->belongsTo('author', 'Author')
            ->by('author_id')
            ->onDelete('CASCADE')
            ;
    }
}
