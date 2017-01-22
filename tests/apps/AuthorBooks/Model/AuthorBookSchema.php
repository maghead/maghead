<?php
namespace AuthorBooks\Model;
use Maghead\Schema;

class AuthorBookSchema extends Schema
{
    public function schema()
    {
        $this->column('author_id')
            ->refer('Author')
            ->required()
            ;

        $this->column('book_id')
            ->refer('Book')
            ->required()
            ;

        $this->column('created_on')
                ->isa('str')
                ->timestamp()
                ;

        $this->belongsTo('book','Book')
            ->by('book_id')
            ->onDelete('CASCADE')
            ->onUpdate('CASCADE')
            ;

        $this->belongsTo('author','Author')
            ->by('author_id')
            ->onDelete('CASCADE')
            ->onUpdate('CASCADE')
            ;
    }
}


