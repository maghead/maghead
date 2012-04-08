<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema;

class AuthorSchema extends SchemaDeclare
{
    function schema()
    {

        $this->column('name')
            ->isa('str')
            ->varchar(128);

        $this->column('email')
            ->isa('str')
            ->required()
            ->varchar(128);

        $this->column('identity')
            ->isa('str')
            ->unique()
            ->required()
            ->varchar(128);

        $this->column('confirmed')
            ->isa('bool')
            ->default(false)
            ->boolean();

        // test for real type
        $this->column('r')
            ->isa('float')
            ->type('float'); // only for pgsql

        $this->mixin('LazyRecord\Schema\Mixin\MetadataSchema');


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
        $this->many( 'addresses', '\tests\AddressSchema', 'author_id', 'id');

        $this->many( 'author_books', '\tests\AuthorBookSchema', 'author_id', 'id');

        $this->manyToMany( 'books', 'author_books' , 'book' );
    }

}
