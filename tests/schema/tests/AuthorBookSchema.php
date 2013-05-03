<?php
namespace tests;
use LazyRecord\Schema;

class AuthorBookSchema extends Schema
{

    function schema()
    {
        $this->column('author_id')
                ->required()
                ->integer();

        $this->column('created_on')
                ->isa('str')
                ->timestamp();

        $this->column('book_id')
            ->integer()
            ->required();

        $this->belongsTo('book','\tests\BookSchema','id','book_id');
        $this->belongsTo('author', '\tests\AuthorSchema' , 'id', 'author_id');
    }
}


