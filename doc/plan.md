Maghead ORM
==============

Requirement
-----------

- Schema export, loader, generator

### Configuration File

LazyORM configuration file:
    
configuration file content:

    ---
    bootstrap:
      - tests/bootstrap.php
    schema:
      paths:
        - tests
    data_sources:
      default:
        dsn: 'sqlite:tests.db'
        # dsn: 'sqlite::memory:'
      slave:
        dsn: 'mysql:host=localhost;dbname=test'
        user: root
        pass: 123123

translate this into php config file `build/datasource_config.php`.

    <?php
    return array(
        'source' => array(
            'master' => array( .... )
        )
    );

### API

Create connection from data source:

    $conn = Maghead\ORM::getConnection('master');

    Maghead\ORM::setConnection( $conn );
    Maghead\ORM::setSchemaLoaderPath( array( 'build/schema' , 'path/to/other/schema' ) );

    $gen = new Maghead\SchemaBuilder;
    $gen->addPath( 'schema/' );
    $gen->setTargetPath( 'build/schema' );
    $gen->build();

    $loader = new SchemaClassLoader;
    $loader->load( 'build/schema/classmap.php' );
    $loader->register();

    $record = new Book;
    $collection = new BookCollection;

Generated Schema class:

    <?php

    class BookSchema extends Maghead\BaseSchema
    {
        function __construct()
        {
            $this->columns = array( 
                ....
            );
            $this->relations = array(
            
            );
        }
    }

Generated Model class:

    <?php

    class Book extends Maghead\Model
    {
        // load schema data in __construct


    }

Generated Collection class:

    <?php
    class BookCollection extends Maghead\Collection
    {

    }



### Model

    $authors = new AuthorCollection;
    $authors->load();
    $authors->where(); ... etc  (can use sqlbuilder for query)

### Schema Columns

- basic column attributes:
    - type: date, varchar with length, text, integer, float ... etc
    - primary key
- a schema writer: write pure PHP schema into JSON or YAML
- type cast.

### Schema Loader Process

When Model object created, try to load the `ModelSchema` file, which is an
array reference that handles.

Should have a global cache for schema array, like Maghead::schemas[ $class ]

    $schema = \Maghead\SchemaLoader::load( $class );

### PHP Schema

```php
<?php
class AuthorSchema extends LazyORM\SchemaBuilder
{

    function schema()
    {
        $this->column('name')
                ->varchar(30)
                ->isa('string') // default, apply String validator
                ->isa('DateTime')  // DateTime object.
                ->isa('integer') // Integer object.

                ->validator('ValidatorClass')
                ->validator( array($validator,'method') )
                ->validator('function_name')
                ->validator(function($val) { .... })

                ->maxLength(30)
                ->minLength(12)

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

Has one Relation:

    just do left join on main table with b.

    $author->profile = array( ... ); // create with current author_id

Has many Relation:

    just do left join on main table with b.

    create:

        $father->children[] = array( ... );   // append one book with current author_id

        /*

        find "children" relationship

        found hasMany relationship

        hasMany "child".

        find hasMany foreight key (fathor_id)

        create nwe child with current fathor_id

        */

    search
    
        $father->children->where()
            ->like( 'name' , '%Bill%' );

        /*

        find "children" relationship

        found hasMany relationship

        hasMany "child".

        find hasMany foreight key (fathor_id)

        select children with where child.fathor_id = self.id

        */

Many to many relation:

    create:

    $author->books[] = array( 'title' => 'New book' );   


    /*
        find "books" relation 

        found many to many relationship:(author => author_books => books)

        find target model (book)

        create new book 

        the "books" is related to "author_books" (book.id belongs to author_books.book_id)

        author_books.author_id belongs to author.id

        create author_book link with (author_id, book_id)
    */


    list:

    foreach( $author->books as $book ) {

    }

    find:

        $author->books->load( 123 );   // find 123 in a subset of author books

ManyToMany relation implementation:

tell schema, `AuthorBook.author_id` is linking to `self.id`

    $authorSchema->addRelation('author_books', 'AuthorBook','author_id',self,'id', SCHEMA::HasMany );

    ( relation key, 'foreign schema', 'foreign key', 'self key', relation type )

tell schema, create a `books` accessor for getting from `AuthorBook->books`:

    authors <=> authors_books <=> books

    $authorSchema->manyToMany('books', 'author_books', 'book_id', SCHEMA::many_to_many );

    ( relation key, relation key, relation foreign key )

To handle many to many relationship, here is the flow:

1. books accessor is created from AuthorBook accessor.
2. get AuthorBook relation from self object
3. find self reference (author.id) and foreign reference (`author_books.author_id`)
4. left join `author_books` on `author.id = author_books.author_id`
5. get Book relation from AuthorBook model.
6. find book reference (book.id) and foreign reference (`author_books.book_id`)
7. left join `books` on `book.id = author_books.book_id`

Here is the code:


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

Implementation:

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




### Validation
- Validation for database
- Validation for Software (Application data), eg: email, string length, password
    - column name validation.
        - validate insert column names ,type, values
        - validate update column names ,type, values
    - parameter validation.
    - chained validators
    - canonicalizer

