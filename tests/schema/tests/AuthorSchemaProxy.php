<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class AuthorSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'name',
  1 => 'email',
  2 => 'identity',
  3 => 'confirmed',
  4 => 'updated_on',
  5 => 'created_on',
  6 => 'id',
);
    public static $column_hash = array (
  'name' => 1,
  'email' => 1,
  'identity' => 1,
  'confirmed' => 1,
  'updated_on' => 1,
  'created_on' => 1,
  'id' => 1,
);
    public static $mixin_classes = array (
  0 => 'LazyRecord\\Schema\\Mixin\\MetadataSchema',
);
    public static $column_names_include_virtual = array (
  0 => 'name',
  1 => 'email',
  2 => 'v',
  3 => 'identity',
  4 => 'confirmed',
  5 => 'updated_on',
  6 => 'created_on',
  7 => 'id',
);

    const schema_class = 'tests\\AuthorSchema';
    const collection_class = 'tests\\AuthorCollection';
    const model_class = 'tests\\Author';
    const model_name = 'Author';
    const model_namespace = 'tests';
    const primary_key = 'id';
    const table = 'authors';
    const label = 'Author';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
        ),
    ),
  'email' => array( 
      'name' => 'email',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'required' => true,
          'size' => 128,
        ),
    ),
  'v' => array( 
      'name' => 'v',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
          'label' => 'Virtual Column',
          'virtual' => true,
          'inflator' => function($value,$record) {
                return $record->email . $record->email;
            },
        ),
    ),
  'identity' => array( 
      'name' => 'identity',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'unique' => true,
          'required' => true,
          'size' => 128,
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
  'updated_on' => array( 
      'name' => 'updated_on',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
          'timezone' => true,
          'default' => function() { 
                return date('c'); 
            },
        ),
    ),
  'created_on' => array( 
      'name' => 'created_on',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
          'timezone' => true,
          'default' => function() { 
                return date('c'); 
            },
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
  'name',
  'email',
  'v',
  'identity',
  'confirmed',
);
        $this->primaryKey      = 'id';
        $this->table           = 'authors';
        $this->modelClass      = 'tests\\Author';
        $this->collectionClass = 'tests\\AuthorCollection';
        $this->label           = 'Author';
        $this->relations       = array( 
  'addresses' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 2,
      'self_column' => 'id',
      'self_schema' => 'tests\\AuthorSchema',
      'foreign_column' => 'author_id',
      'foreign_schema' => '\\tests\\AddressSchema',
    ),
)),
  'author_books' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 2,
      'self_column' => 'id',
      'self_schema' => 'tests\\AuthorSchema',
      'foreign_column' => 'author_id',
      'foreign_schema' => '\\tests\\AuthorBookSchema',
    ),
)),
  'books' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 3,
      'relation_junction' => 'author_books',
      'relation_foreign' => 'book',
    ),
)),
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
