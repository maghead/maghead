<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class AuthorBookSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'author_id',
  1 => 'created_on',
  2 => 'book_id',
  3 => 'id',
);
    public static $column_hash = array (
  'author_id' => 1,
  'created_on' => 1,
  'book_id' => 1,
  'id' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'author_id',
  1 => 'created_on',
  2 => 'book_id',
  3 => 'id',
);

    const schema_class = 'tests\\AuthorBookSchema';
    const collection_class = 'tests\\AuthorBookCollection';
    const model_class = 'tests\\AuthorBook';
    const model_name = 'AuthorBook';
    const model_namespace = 'tests';
    const primary_key = 'id';
    const table = 'author_books';
    const label = 'AuthorBook';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'author_id' => array( 
      'name' => 'author_id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'required' => true,
        ),
    ),
  'created_on' => array( 
      'name' => 'created_on',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
        ),
    ),
  'book_id' => array( 
      'name' => 'book_id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'required' => true,
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
  'author_id',
  'created_on',
  'book_id',
);
        $this->primaryKey      = 'id';
        $this->table           = 'author_books';
        $this->modelClass      = 'tests\\AuthorBook';
        $this->collectionClass = 'tests\\AuthorBookCollection';
        $this->label           = 'AuthorBook';
        $this->relations       = array( 
  'book' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 4,
      'self_schema' => 'tests\\AuthorBookSchema',
      'self_column' => 'book_id',
      'foreign_schema' => '\\tests\\BookSchema',
      'foreign_column' => 'id',
    ),
)),
  'author' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 4,
      'self_schema' => 'tests\\AuthorBookSchema',
      'self_column' => 'author_id',
      'foreign_schema' => '\\tests\\AuthorSchema',
      'foreign_column' => 'id',
    ),
)),
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
