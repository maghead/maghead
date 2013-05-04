<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class IDNumberSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'id_number',
  1 => 'id',
);
    public static $column_hash = array (
  'id_number' => 1,
  'id' => 1,
);
    public static $mixin_classes = array (
);
    public static $column_names_include_virtual = array (
  0 => 'id_number',
  1 => 'id',
);

    const schema_class = 'LazyRecord\\Schema\\DynamicSchemaDeclare';
    const collection_class = 'tests\\IDNumberCollection';
    const model_class = 'tests\\IDNumber';
    const model_name = 'IDNumber';
    const model_namespace = 'tests';
    const primary_key = 'id';
    const table = 'i_d_numbers';
    const label = 'IDNumber';

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
