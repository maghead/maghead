<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

class AuthorSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns         = array( 
  'created_on' => array( 
      'name' => 'created_on',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
          'defaultBuilder' => function() { return date('c'); },
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
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
        ),
    ),
  'email' => array( 
      'name' => 'email',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'required' => true,
        ),
    ),
  'identity' => array( 
      'name' => 'identity',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'unique' => true,
          'required' => true,
        ),
    ),
  'confirmed' => array( 
      'name' => 'confirmed',
      'attributes' => array( 
          'type' => 'boolean',
          'isa' => 'bool',
          'default' => false,
        ),
    ),
);
        $this->columnNames     = array( 
  'name',
  'email',
  'identity',
  'confirmed',
);
        $this->primaryKey      = 'id';
        $this->table           = 'authors';
        $this->modelClass      = 'tests\\Author';
        $this->collectionClass = 'tests\\AuthorCollection';
        $this->label           = 'Author';
        $this->relations       = array( 
  'addresses' => array( 
      'type' => 2,
      'self' => array( 
          'column' => 'id',
          'schema' => 'tests\\AuthorSchema',
        ),
      'foreign' => array( 
          'column' => 'author_id',
          'schema' => '\\tests\\AddressSchema',
        ),
    ),
  'author_books' => array( 
      'type' => 2,
      'self' => array( 
          'column' => 'id',
          'schema' => 'tests\\AuthorSchema',
        ),
      'foreign' => array( 
          'column' => 'author_id',
          'schema' => '\\tests\\AuthorBookSchema',
        ),
    ),
  'books' => array( 
      'type' => 3,
      'relation' => array( 
          'id' => 'author_books',
          'id2' => 'book',
        ),
    ),
);
    }

}
