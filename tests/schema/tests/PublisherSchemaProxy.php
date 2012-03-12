<?php
namespace tests;

use Lazy\Schema\RuntimeSchema;

class PublisherSchemaProxy extends RuntimeSchema
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
          'type' => 'integer',
          'isa' => 'int',
          'primary' => true,
          'autoIncrement' => true,
        ),
    ),
);
        $this->columnNames = array( 
  'name',
  'id',
);
        $this->primaryKey  = 'id';
        $this->table       = 'publishers';
        $this->modelClass  = 'tests\\Publisher';
        $this->label       = 'Publisher';
        $this->relations   = array( 
);
    }

}
