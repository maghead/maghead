<?php
namespace AuthorBooks\Model;
use LazyRecord\Schema;

class BookSchema extends Schema
{

    public function schema()
    {
        $this->column('title')
            ->unique()
            ->varchar(128);

        $this->column('subtitle')
            ->varchar(256);

        $this->column('isbn')
            ->varchar(128)
            ->immutable()
            ;

        $this->column('description')
            ->text();

        $this->column('view')
            ->default(0)
            ->integer();

        $this->column('publisher_id')
            ->isa('int')
            ->integer();

        $this->column('published_at')
            ->isa('DateTime')
            ->timestamp();

        // Create a flag column named "is_hot" labeld "Hot Sale", checked by default
        $this->helper('Flag', ['is_hot','Hot Sale', true]);
        $this->helper('Flag', ['is_selled','Selled', false]);


        /** 
         * Column: author => Author class 
         *
         * $book->publisher->name;
         **/
        $this->belongsTo('publisher','AuthorBooks\Model\PublisherSchema', 'id', 'publisher_id');

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', 'AuthorBooks\Model\AuthorBookSchema', 'book_id', 'id');

        $this->addModelTrait('TestApp\ModelTrait\EBookTrait');

        /**
         * get BookAuthor.author 
         */
        $this->manyToMany('authors', 'book_authors', 'author' )
            ->filter(function($collection) {
                return $collection; 
            });
    }

}
