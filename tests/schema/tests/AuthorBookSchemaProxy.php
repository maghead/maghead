<?php
namespace tests;

use Lazy\Schema\RuntimeSchema;

class AuthorBookSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns     = array( 
  'author_id' => array( 
      'name' => 'author_id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
        ),
    ),
  'book_id' => array( 
      'name' => 'book_id',
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
        $this->columnNames = array( 
  'author_id',
  'book_id',
  'id',
);
        $this->primaryKey  = 'id';
        $this->table       = 'author_books';
        $this->modelClass  = 'tests\\AuthorBook';
        $this->label       = 'AuthorBook';
        $this->relations   = array( 
  'book' => array( 
      'type' => 1,
      'self' => array( 
          'column' => 'book_id',
          'schema' => 'tests\\AuthorBookSchemaProxy',
        ),
      'foreign' => array( 
          'column' => 'id',
          'schema' => '\\tests\\BookSchema',
        ),
    ),
  'author' => array( 
      'type' => 1,
      'self' => array( 
          'column' => 'author_id',
          'schema' => 'tests\\AuthorBookSchemaProxy',
        ),
      'foreign' => array( 
          'column' => 'id',
          'schema' => '\\tests\\AuthorSchema',
        ),
    ),
);
    }

}
