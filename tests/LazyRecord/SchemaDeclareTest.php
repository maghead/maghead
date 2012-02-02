<?php
namespace tests;

class AuthorSchema extends \LazyRecord\SchemaDeclare
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

        $this->hasOne('book'   , 'book_id'   , 'Book'   , 'id' );

        $this->hasOne('author' , 'author_id' , 'Author' , 'id' );
    }
}

class BookSchema extends \LazyRecord\SchemaDeclare
{

    function schema()
    {
        $this->column('id')
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
         * $book->author->title;
         *
         **/
        $this->hasOne('publisher', 'publisher_id', 'Publisher', 'id' );

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->hasMany('book_authors', 'AuthorBook', 'book_id', 'id');


        /**
         * get BookAuthor.author 
         */
        $this->manyToMany( 'authors', 'book_authors', 'author' );
    }

}

namespace main;

class SchemaDeclareTest extends \PHPUnit_Framework_TestCase
{

    public function testAuthor()
    {
        $declare = new \tests\AuthorSchema;
        $declare->build();

    }

    public function test()
    {
        $declare = new \tests\BookSchema;
        ok( $declare , 'schema ok' );

        $declare->build();
        ok( $declare->columns , 'columns' );
        ok( $declare->columns );
        ok( $c = $declare->columns['title'] );
        ok( $c = $declare->columns['subtitle'] );
        ok( $c = $declare->columns['description'] );


        is( 'tests\Book' , $declare->getModelClass() );
        is( 'books' , $declare->getTable() );

        $schemaArray = $declare->export();
        ok( $schemaArray );

        $schema = new \LazyRecord\Schema;
        $schema->import( $schemaArray );

        ok( $schema );
    }
}

