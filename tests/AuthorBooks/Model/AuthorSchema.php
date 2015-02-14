<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class AuthorSchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128);

        $this->column('email')
            ->required()
            ->varchar(128);

        $this->column('v')
            ->label('Virtual Column')
            ->virtual()
            ->inflator(function($value,$record) {
                return $record->email . $record->email;
            });

        $this->column('identity')
            ->unique()
            ->required()
            ->varchar(128);

        $this->column('confirmed')
            ->boolean()
            ->default(false);

        $this->mixin('LazyRecord\\Schema\\Mixin\\MetadataSchema');

        $this->many('addresses', 'AuthorBooks\Model\AddressSchema', 'author_id', 'id');

        $this->many('unused_addresses', 'AuthorBooks\Model\AddressSchema', 'author_id', 'id')
            ->where()
                ->equal('unused', true);

        $this->many('author_books', 'AuthorBooks\Model\AuthorBookSchema', 'author_id', 'id');

        $this->manyToMany('books', 'author_books' , 'book');
    }

}
