<?php
namespace TestApp;
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


        

        /**
         * for append:
         *
         *     $author->address[] = array(  );
         *
         *     $record = $author->createAddress(array( ... ));  // return false on failure.
         *
         * for fetch:
         *
         *     foreach( $author->addresses as $address ) {
         *
         *     }
         *
         * for search/find:
         *
         *     $address = $author->addresses->find(k);
         *
         */
        $this->many( 'addresses', '\TestApp\AddressSchema', 'author_id', 'id');

        $this->many( 'author_books', '\TestApp\AuthorBookSchema', 'author_id', 'id');

        $this->manyToMany( 'books', 'author_books' , 'book' );
    }

}
