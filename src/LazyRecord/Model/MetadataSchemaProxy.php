<?php
namespace LazyRecord\Model;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class MetadataSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'id',
  1 => 'name',
  2 => 'value',
);
    public static $column_hash = array (
  'id' => 1,
  'name' => 1,
  'value' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'id',
  1 => 'name',
  2 => 'value',
);

    const schema_class = 'LazyRecord\\Schema\\DynamicSchemaDeclare';
    const collection_class = 'LazyRecord\\Model\\MetadataCollection';
    const model_class = 'LazyRecord\\Model\\Metadata';
    const model_name = 'Metadata';
    const model_namespace = 'LazyRecord\\Model';
    const primary_key = 'id';
    const table = '__meta__';
    const label = 'Metadata';

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
