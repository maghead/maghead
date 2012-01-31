LazyRecord ORM
==============

Requirement
-----------

### Schema Columns

- basic column attributes:
    - type: date, varchar with length, text, integer, float ... etc
    - primary key
- a schema writer: write pure PHP schema into JSON or YAML
- type cast.


### PHP Schema

```php
<?php
class AuthorSchema 
{

    function schema()
    {
        $this->column('name')->varchar(30)
                ->validator('ValidatorClass')
                ->canonicalizer('CanonicalClass')
                ->default('Default')
                ->validValues( 1,2,3,4,5 )
                ->validValues( array( 'label' => 'value'  ) );

        // type is inherited from author.id column (bigint or string)
        $this->column('publisher_id')
                ->reference('Publisher','id', schema::has_many ); 

    }
}
```


### Relationship

HasOne Relation:


HasMany Relation


ManyToMany Relation:

tell schema, `AuthorBook.author_id` is linking to `self.id`

    $authorSchema->addRelation('author_books', 'AuthorBook','author_id',self,'id', SCHEMA::HasMany );

    ( relation key, 'foreign schema', 'foreign key', 'self key', relation type )

tell schema, create a `books` accessor for getting from `AuthorBook->books`:

    authors <=> authors_books <=> books

    $authorSchema->manyToMany('books', 'author_books', 'book_id', SCHEMA::many_to_many );

    ( relation key, relation key, relation foreign key )

This can create a SQL query by:

1. books accessor is created from AuthorBook accessor.
2. get AuthorBook relation from self object
3. find self reference (author.id) and foreign reference (`author_books.author_id`)
4. left join `author_books` on `author.id = author_books.author_id`
5. get Book relation from AuthorBook model.
6. find book reference (book.id) and foreign reference (`author_books.book_id`)
7. left join `books` on `book.id = author_books.book_id`

Here is the code:

    class Relation {
        public $key; // relation key
        public $type; // relation type: many to many, has many

        public $selfSchema;
        public $foreignSchema;

        public $selfKey;
        public $foreignKey;
    }

    class Accessor
    {

    }

    class Model 
    {

        /**
         *    relation key => relation object
         */
        public $relations = array();


        function __get($key)
        {
            if( isset($this->relations[ $key ] ) && $r = $this->relations[$key] ) {
                $r->type // many to many or (has many)

            }


        }

        public function author_books()
        {
            $accessor = $thisSchema->getAccessor('author_books'); // which relates to author_books
            $relation = $thisSchema->getRelation( $accessor->getRelationKey() );
            $schema     = $relation->getForeignSchema();
            $selfKey    = $relation->getSelfPk();
            $foreignKey = $relation->getForeignPk();

            $sql = $query->where()
                ->leftJoin( $schema->table )
                ->on()
                    ->equal( $selfKey , $foreignKey )->back()->build();
        }

        public function books()
        {
            $accessor = $thisSchema->getAccessor('books'); // which relates to author_books

            $joinQueue = array();

            $relation = $thisSchema->getRelation( $accessor->getRelationKey() );
            $relationSchema = $relation->getForeignSchema();

            $ab_author_pk = $relation->getForeignPk(); // author_books.author_id
            $a_pk = $relation->getSelfPk();    // author.id

            // books relation in author_books, defines: self.book_id => books.id
            $subRelation = $relationSchema->getRelation('books');   
            $subRelationSchema = $subRelation->getForeignSchema();

            $b_pk = $subRelation->getForeignPk();
            $ab_book_pk = $subRelation->getSelfPk();

            $this->select('*')->table( $subRelationSchema->getTable() )
                ->leftJoin( $relationSchema->getTable() )
                        ->on()->equal( $ab_book_pk , $b_pk )
                        ->back()
                ->leftJoin( $this->getTable() )
                        ->on()->equal( $ab_author_pk , $a_pk )
                        ->back();
        }

        function books()
        {
            $accessor = $thisSchema->getAccessor('books'); // which relates to author_books

            $joinQueue = array();

            $relation = $thisSchema->getRelation( $accessor->getRelationKey() );
            $relationSchema = $relation->getForeignSchema();
            $joinQueue[] = array( $relationSchema->getTable(), 
                $relation->getSelfPk(), 
                $relation->getForeignPk() );


            // books relation in author_books, defines: self.book_id => books.id
            $subRelation = $relationSchema->getRelation('books');   
            $subRelationSchema = $subRelation->getForeignSchema();
            $joinQueue[] = array( $relationSchema->getTable(), 
                    $subRelation->getSelfPk(), 
                    $subRelation->getForeignPk() );

            foreach( $joinQueue as $join ) {
                list( $table, $sKey, $fKey) = $join;
                $this->leftJoin( $table )->on()->equal( $sKey, $fKey );
            }
        }

    }

To retrieve books from author
    
    foreach( $author->books as $book ) {
        $title = $book->title;     
    }

should create a query:

    SELECT books.* from books b
        LEFT JOIN author_books ab ON (b.id = ab.book_id)
        LEFT JOIN authors a ON (a.id = ab.author_id)
        where a.id = :author_id

(is `->title` through `__get` faster than native property? 
or better than getTitle() ? )





### Validation
- Validation for database
- Validation for Software (Application data), eg: email, string length, password
    - column name validation.
        - validate insert column names ,type, values
        - validate update column names ,type, values
    - parameter validation.
    - chained validators
    - canonicalizer



