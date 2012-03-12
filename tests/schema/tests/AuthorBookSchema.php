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

        $this->column('created_on')
                ->isa('str')
                ->timestamp();

        $this->column('book_id')
                ->isa('int')
                ->integer();

        $this->belongsTo('book'   , 'book_id'   , '\tests\BookSchema'   , 'id' );

        $this->belongsTo('author' , 'author_id' , '\tests\AuthorSchema' , 'id' );
    }
}


