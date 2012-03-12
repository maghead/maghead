<?php
namespace tests;

use Lazy\Schema\RuntimeSchema;

class AddressSchemaProxy extends RuntimeSchema
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
  'address' => array( 
      'name' => 'address',
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
        $this->columnNames = array( 
  'author_id',
  'address',
  'id',
);
        $this->primaryKey  = 'id';
        $this->table       = 'addresses';
        $this->modelClass  = 'tests\\Address';
        $this->label       = 'Address';
    }

}
