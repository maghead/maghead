<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class AddressSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'author_id',
  1 => 'address',
  2 => 'foo',
  3 => 'id',
);
    public static $column_hash = array (
  'author_id' => 1,
  'address' => 1,
  'foo' => 1,
  'id' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'author_id',
  1 => 'address',
  2 => 'foo',
  3 => 'id',
);

    const schema_class = 'tests\\AddressSchema';
    const collection_class = 'tests\\AddressCollection';
    const model_class = 'tests\\Address';
    const primary_key = 'id';
    const table = 'addresses';
    const label = 'Address';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'author_id' => array( 
      'name' => 'author_id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
        ),
    ),
  'address' => array( 
      'name' => 'address',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
        ),
    ),
  'foo' => array( 
      'name' => 'foo',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
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
  'address',
  'foo',
);
        $this->primaryKey      = 'id';
        $this->table           = 'addresses';
        $this->modelClass      = 'tests\\Address';
        $this->collectionClass = 'tests\\AddressCollection';
        $this->label           = 'Address';
        $this->relations       = array( 
  'author' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 4,
      'self_schema' => 'tests\\AddressSchema',
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
