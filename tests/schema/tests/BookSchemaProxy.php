<?php
namespace tests;

use Lazy\Schema\RuntimeSchema;

class BookSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns         = array( 
  'title' => array( 
      'name' => 'title',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'unique' => true,
        ),
    ),
  'subtitle' => array( 
      'name' => 'subtitle',
      'attributes' => array( 
          'type' => 'varchar(256)',
          'isa' => 'str',
        ),
    ),
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
        ),
    ),
  'publisher_id' => array( 
      'name' => 'publisher_id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
        ),
    ),
  'published_at' => array( 
      'name' => 'published_at',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
        ),
    ),
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'primary' => true,
          'autoIncrement' => true,
        ),
    ),
);
        $this->columnNames     = array( 
  'title',
  'subtitle',
  'description',
  'publisher_id',
  'published_at',
  'id',
);
        $this->primaryKey      = 'id';
        $this->table           = 'books';
        $this->modelClass      = 'tests\\Book';
        $this->collectionClass = 'tests\\BookCollection';
        $this->label           = 'Book';
        $this->relations       = array( 
  'publisher' => array( 
      'type' => 1,
      'self' => array( 
          'column' => 'publisher_id',
          'schema' => 'tests\\BookSchemaProxy',
        ),
      'foreign' => array( 
          'column' => 'id',
          'schema' => '\\tests\\PublisherSchema',
        ),
    ),
  'book_authors' => array( 
      'type' => 2,
      'self' => array( 
          'column' => 'id',
          'schema' => 'tests\\BookSchema',
        ),
      'foreign' => array( 
          'column' => 'book_id',
          'schema' => '\\tests\\AuthorBookSchema',
        ),
    ),
  'authors' => array( 
      'type' => 3,
      'relation' => 'book_authors',
      'relation_foreign_key' => 'author',
    ),
);
    }

}
