<?php

namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;
use Magsql\Raw;

use Maghead\Schema\Mixin\MetadataMixinSchema;

class BookSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('title')
            ->varchar(128);

        $this->index('idx_book_title_subtitle', [ 'title', 'subtitle' ]);

        $this->column('subtitle')
            ->varchar(256);

        $this->column('isbn')
            ->unique()
            ->varchar(128)
            ->immutable()
            ->findable()
            ;

        $this->column('description')
            ->text();


        $this->column('category_id')
            ->integer()
            ->unsigned()
            ->default(null)
            ;

        $this->column('view')
            ->default(0)
            ->integer();

        $this->column('published')
            ->boolean()
            ->default(false)
            ;

        $this->column('publisher_id')
            ->integer()
            ->unsigned()
            ;

        $this->column('published_at')
            ->isa('DateTime')
            ->timestamp()
            ->default(function () {
                return new \DateTime;
            })
            ;


        // Create a flag column named "is_hot" labeld "Hot Sale", checked by default
        $this->helper('Flag', ['is_hot','Hot Sale', true]);
        $this->helper('Flag', ['is_selled','Selled', false]);


        $this->mixin(MetadataMixinSchema::class);


        /**
         * Column: author => Author class
         *
         * $book->publisher->name;
         **/
        $this->belongsTo('publisher', PublisherSchema::class, 'id', 'publisher_id');

        $this->belongsTo('category', CategorySchema::class, 'id', 'category_id');



        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', AuthorBookSchema::class, 'book_id', 'id');

        $this->classes->baseModel->useTrait('TestApp\ModelTrait\EBookTrait');
        $this->classes->baseModel->implementInterface('TestApp\ModelInterface\EBookInterface');

        $this->classes->baseCollection->useTrait('TestApp\ModelTrait\EBookCollectionTrait');
        $this->classes->baseCollection->implementInterface('TestApp\ModelInterface\EBookCollectionInterface');

        $this->manyToMany('authors', 'book_authors', 'author')
            ->filter(function ($collection) {
                return $collection;
            });
    }
}
