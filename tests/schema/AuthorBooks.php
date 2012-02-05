<?php
namespace tests {
use LazyRecord\SchemaDeclare;

class PublisherSchema extends SchemaDeclare
{

    function schema()
    {
        $this->column('id')
            ->type('integer')
            ->primary()
            ->autoIncrement();
        $this->column('name')
            ->isa('str')
            ->varchar(128);
    }

}

class AuthorSchema extends \LazyRecord\SchemaDeclare
{
    function schema()
    {
        $this->column('id')
            ->type('integer')
            ->isa('int')
            ->primary()
            ->autoIncrement();

        $this->column('name')
            ->isa('str')
            ->varchar(128);

        $this->column('email')
            ->isa('str')
            ->required()
            ->varchar(128);

        $this->column('identity')
            ->isa('str')
            ->unique()
            ->required()
            ->varchar(128);

        $this->column('confirmed')
            ->isa('bool')
            ->default(false)
            ->boolean();

        $this->column('created_on')
            ->isa('DateTime')
            ->defaultBuilder( function() { return date('c'); } )
            ->timestamp();

        // $this->belongsTo( '\tests\AuthorBookSchema' , 'author_id' );
    }

}

class AuthorBookSchema extends \LazyRecord\SchemaDeclare
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

class BookSchema extends \LazyRecord\SchemaDeclare
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
