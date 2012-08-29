<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

class AddressSchemaProxy extends RuntimeSchema
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
        ),
    ),
  'address' => array( 
      'name' => 'address',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
        ),
    ),
  'foo' => array( 
      'name' => 'foo',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
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
  'author' => array( 
      'type' => 4,
      'self' => array( 
          'schema' => 'tests\\AddressSchema',
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
