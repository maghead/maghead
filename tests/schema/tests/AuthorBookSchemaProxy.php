<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

class AuthorBookSchemaProxy extends RuntimeSchema
{

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
  'author_id',
  'created_on',
  'book_id',
  'id',
);
        $this->primaryKey      = 'id';
        $this->table           = 'author_books';
        $this->modelClass      = 'tests\\AuthorBook';
        $this->collectionClass = 'tests\\AuthorBookCollection';
        $this->label           = 'AuthorBook';
        $this->relations       = array( 
  'book' => array( 
      'type' => 4,
      'self' => array( 
          'schema' => 'tests\\AuthorBookSchema',
          'column' => 'book_id',
        ),
      'foreign' => array( 
          'schema' => '\\tests\\BookSchema',
          'column' => 'id',
        ),
    ),
  'author' => array( 
      'type' => 4,
      'self' => array( 
          'schema' => 'tests\\AuthorBookSchema',
          'column' => 'author_id',
        ),
      'foreign' => array( 
          'schema' => '\\tests\\AuthorSchema',
          'column' => 'id',
        ),
    ),
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';

        parent::__construct();
    }

}
