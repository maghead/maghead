<?php
namespace LazyRecord\Model;

use LazyRecord\Schema\RuntimeSchema;

class MetadataSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
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
          'size' => 128,
        ),
    ),
  'value' => array( 
      'name' => 'value',
      'attributes' => array( 
          'type' => 'varchar(256)',
          'isa' => 'str',
          'size' => 256,
        ),
    ),
);
        $this->columnNames     = array( 
  'id',
  'name',
  'value',
);
        $this->primaryKey      = 'id';
        $this->table           = '__meta__';
        $this->modelClass      = 'LazyRecord\\Model\\Metadata';
        $this->collectionClass = 'LazyRecord\\Model\\MetadataCollection';
        $this->label           = 'Metadata';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
