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
                ->refer('AuthorBooks\\Model\\AuthorSchema');
                ;

        $this->column('book_id')
            ->integer()
            ->unsigned()
            ->required();

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


