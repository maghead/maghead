<?php
namespace tests;

use Lazy\Schema;

class PublisherSchemaProxy extends Schema
{

    public function __construct()
    {
        $this->columns = array( 
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'isa' => 'str',
          'type' => 'varchar(128)',
        ),
    ),
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'isa' => 'int',
          'primary' => true,
        ),
    ),
);
        $this->columnNames = array( 
  'name',
  'id',
);
        $this->primaryKey =  'id';
        $this->table = 'publishers';
        $this->modelClass = 'tests\\Publisher';
    }

}
