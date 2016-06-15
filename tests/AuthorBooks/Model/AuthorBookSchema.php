<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class AuthorBookSchema extends Schema
{
    public function schema()
    {
        $this->column('author_id')
                ->required()
                ->integer()
                ->unsigned()
                ;

        $this->column('book_id')
            ->integer()
            ->unsigned()
            ->required();

        $this->column('created_on')
                ->isa('str')
                ->timestamp();

        $this->belongsTo('book','\AuthorBooks\Model\BookSchema','id','book_id')
            ->onDelete('CASCADE')
            ->onUpdate('CASCADE')
            ;
        $this->belongsTo('author', '\AuthorBooks\Model\AuthorSchema' , 'id', 'author_id')
            ->onDelete('CASCADE')
            ->onUpdate('CASCADE')
            ;
    }
}


