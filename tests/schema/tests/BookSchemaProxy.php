<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class BookSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'title',
  1 => 'subtitle',
  2 => 'isbn',
  3 => 'description',
  4 => 'view',
  5 => 'publisher_id',
  6 => 'published_at',
  7 => 'created_by',
  8 => 'id',
);
    public static $column_hash = array (
  'title' => 1,
  'subtitle' => 1,
  'isbn' => 1,
  'description' => 1,
  'view' => 1,
  'publisher_id' => 1,
  'published_at' => 1,
  'created_by' => 1,
  'id' => 1,
);
    public static $mixin_classes = array (
);
    public static $column_names_include_virtual = array (
  0 => 'title',
  1 => 'subtitle',
  2 => 'isbn',
  3 => 'description',
  4 => 'view',
  5 => 'publisher_id',
  6 => 'published_at',
  7 => 'created_by',
  8 => 'id',
);

    const schema_class = 'tests\\BookSchema';
    const collection_class = 'tests\\BookCollection';
    const model_class = 'tests\\Book';
    const model_name = 'Book';
    const model_namespace = 'tests';
    const primary_key = 'id';
    const table = 'books';
    const label = 'Book';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
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
          'size' => 256,
        ),
    ),
  'isbn' => array( 
      'name' => 'isbn',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
          'immutable' => true,
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
          'timestamp' => true,
        ),
    ),
  'created_by' => array( 
      'name' => 'created_by',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'refer' => '\\tests\\UserSchema',
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
  'id',
  'title',
  'subtitle',
  'isbn',
  'description',
  'view',
  'publisher_id',
  'published_at',
  'created_by',
);
        $this->primaryKey      = 'id';
        $this->table           = 'books';
        $this->modelClass      = 'tests\\Book';
        $this->collectionClass = 'tests\\BookCollection';
        $this->label           = 'Book';
        $this->relations       = array( 
  'created_by' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 4,
      'self_schema' => 'tests\\BookSchema',
      'self_column' => 'created_by',
      'foreign_schema' => '\\tests\\UserSchema',
      'foreign_column' => 'id',
    ),
)),
  'publisher' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 4,
      'self_schema' => 'tests\\BookSchema',
      'self_column' => 'publisher_id',
      'foreign_schema' => '\\tests\\PublisherSchema',
      'foreign_column' => 'id',
    ),
)),
  'book_authors' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 2,
      'self_column' => 'id',
      'self_schema' => 'tests\\BookSchema',
      'foreign_column' => 'book_id',
      'foreign_schema' => '\\tests\\AuthorBookSchema',
    ),
)),
  'authors' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 3,
      'relation_junction' => 'book_authors',
      'relation_foreign' => 'author',
      'filter' => function($collection) { return $collection; },
    ),
)),
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
