<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class IDNumberSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'id_number' => array( 
      'name' => 'id_number',
      'attributes' => array( 
          'type' => 'varchar(10)',
          'isa' => 'str',
          'size' => 10,
          'validator' => 'ValidationKit\\TW\\IDNumberValidator',
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
  'id_number',
);
        $this->primaryKey      = 'id';
        $this->table           = 'i_d_numbers';
        $this->modelClass      = 'tests\\IDNumber';
        $this->collectionClass = 'tests\\IDNumberCollection';
        $this->label           = 'IDNumber';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
