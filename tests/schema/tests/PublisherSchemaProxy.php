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
);
        $this->columnNames = array( 
  'name',
);
        $this->primaryKey =  NULL;
        $this->table = 'publishers';
        $this->modelClass = 'tests\\Publisher';
    }

}
