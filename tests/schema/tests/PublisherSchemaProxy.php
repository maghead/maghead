<?php
namespace tests;

use Lazy\Schema;

class PublisherSchemaProxy extends Schema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns     = array( 
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
        ),
    ),
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'int',
          'primary' => true,
        ),
    ),
);
        $this->columnNames = array( 
  'name',
  'id',
);
        $this->primaryKey  =  'id';
        $this->table       = 'publishers';
        $this->modelClass  = 'tests\\Publisher';
        $this->label       = 'Publisher';
    }

}
