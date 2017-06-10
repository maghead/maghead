<?php
namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;

class AuthorBookSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('author_id')
            ->refer(AuthorSchema::class)
            ->required()
            ;

        $this->column('book_id')
            ->refer(BookSchema::class)
            ->required()
            ;

        $this->column('created_at')
                ->isa('str')
                ->timestamp()
                ;

        $this->belongsTo('book', 'Book')
            ->by('book_id')
            ->onDelete('CASCADE')
            ->onUpdate('CASCADE')
            ;

        $this->belongsTo('author', 'Author')
            ->by('author_id')
            ->onDelete('CASCADE')
            ->onUpdate('CASCADE')
            ;
    }
}
