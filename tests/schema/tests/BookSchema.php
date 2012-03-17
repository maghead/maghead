<?php
namespace tests;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema;

class BookSchema extends SchemaDeclare
{

    function schema()
    {
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

        $this->column('created_by')
            ->integer()
            ->refer('\tests\UserSchema');

        $this->belongsTo('created_by', 'created_by', '\tests\UserSchema','id');

        /** 
         * column: author => Author class 
         *
         * $book->publisher->name;
         *
         **/
        $this->belongsTo('publisher', 'publisher_id', '\tests\PublisherSchema', 'id' );

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', '\tests\AuthorBookSchema', 'book_id', 'id');


        /**
         * get BookAuthor.author 
         */
        $this->manyToMany( 'authors', 'book_authors', 'author' );
    }

}
