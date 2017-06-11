<?php
namespace AuthorBooks\Model;

use Maghead\Schema\DeclareSchema;
use Magsql\Raw;

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

        $this->column('updated_at')
            ->timestamp()
            ->null()
            ->isa('DateTime')
            ->renderAs('DateTimeInput')
            ->default(new Raw('CURRENT_TIMESTAMP'))
            ->onUpdate(new Raw('CURRENT_TIMESTAMP'))
            ->label('Updated at')
            ;

        $this->column('created_at')
            ->timestamp()
            ->isa('DateTime')
            ->null()
            ->renderAs('DateTimeInput')
            ->label( _('建立時間') )
            ->default(function() {
                return new \DateTime;
            })
            ;

        // Create a flag column named "is_hot" labeld "Hot Sale", checked by default
        $this->helper('Flag', ['is_hot','Hot Sale', true]);
        $this->helper('Flag', ['is_selled','Selled', false]);


        /**
         * Column: author => Author class
         *
         * $book->publisher->name;
         **/
        $this->belongsTo('publisher', PublisherSchema::class, 'id', 'publisher_id');

        /**
         * accessor , mapping self.id => BookAuthors.book_id
         *
         * link book => author_books
         */
        $this->many('book_authors', AuthorBookSchema::class, 'book_id', 'id');

        $this->classes->model->useTrait('TestApp\ModelTrait\EBookTrait');
        $this->classes->model->implementInterface('TestApp\ModelInterface\EBookInterface');

        $this->classes->collection->useTrait('TestApp\ModelTrait\EBookCollectionTrait');
        $this->classes->collection->implementInterface('TestApp\ModelInterface\EBookCollectionInterface');


        /**
         * get BookAuthor.author
         */
        $this->manyToMany('authors', 'book_authors', 'author')
            ->filter(function ($collection) {
                return $collection;
            });
    }
}
