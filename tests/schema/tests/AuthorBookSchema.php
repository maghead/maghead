<?php
namespace tests;
use Lazy\Schema\SchemaDeclare;

class AuthorBookSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('author_id')
                ->isa('int')
                ->integer();

        $this->column('book_id')
                ->isa('int')
                ->integer();

        $this->hasOne('book'   , 'book_id'   , '\tests\BookSchema'   , 'id' );

        $this->hasOne('author' , 'author_id' , '\tests\AuthorSchema' , 'id' );
    }
}


