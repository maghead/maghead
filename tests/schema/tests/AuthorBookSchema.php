<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;

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

        $this->belongsTo('book','\tests\BookSchema','id','book_id');
        $this->belongsTo('author', '\tests\AuthorSchema' , 'id', 'author_id');
    }
}


