<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

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
  'view' => array( 
      'name' => 'view',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'default' => 0,
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
  'created_by' => array( 
      'name' => 'created_by',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
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
  'view',
  'publisher_id',
  'published_at',
  'created_by',
  'id',
);
        $this->primaryKey      = 'id';
        $this->table           = 'books';
        $this->modelClass      = 'tests\\Book';
        $this->collectionClass = 'tests\\BookCollection';
        $this->label           = 'Book';
        $this->relations       = array( 
  'created_by' => array( 
      'type' => 4,
      'self' => array( 
          'schema' => 'tests\\BookSchema',
          'column' => 'created_by',
        ),
      'foreign' => array( 
          'schema' => '\\tests\\UserSchema',
          'column' => 'id',
        ),
    ),
  'publisher' => array( 
      'type' => 4,
      'self' => array( 
          'schema' => 'tests\\BookSchema',
          'column' => 'publisher_id',
        ),
      'foreign' => array( 
          'schema' => '\\tests\\PublisherSchema',
          'column' => 'id',
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
      'relation' => array( 
          'id' => 'book_authors',
          'id2' => 'author',
        ),
    ),
);
    }

}
