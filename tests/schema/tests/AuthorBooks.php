<?php
namespace tests {
use Lazy\Schema\SchemaDeclare;
use Lazy\Schema;

class MetadataMixinSchema extends Schema\MixinSchemaDeclare
{

    function schema()
    {
        $this->column('created_on')
            ->isa('DateTime')
            ->defaultBuilder( function() { return date('c'); } )
            ->timestamp();
    }
}


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

class BookSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('id')
            ->integer()
            ->primary()
            ->autoIncrement();

        $this->column('title')
            ->isa('str')
            ->unique()
            ->type('varchar(128)');

        $this->column('subtitle')
            ->isa('str')
            ->varchar(256);

        $this->column('description')
            ->isa('str')
            ->text();

        $this->column('publisher_id')
            ->isa('int')
            ->integer();

        $this->column('published_at')
            ->isa('DateTime')
            ->timestamp();

        /** 
         * column: author => Author class 
         *
         * $book->publisher->name;
         *
         **/
        $this->hasOne('publisher', 'publisher_id', '\tests\PublisherSchema', 'id' );

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->hasMany('book_authors', '\tests\AuthorBookSchema', 'book_id', 'id');


        /**
         * get BookAuthor.author 
         */
        $this->manyToMany( 'authors', 'book_authors', 'author' );
    }

}

}
