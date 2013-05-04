<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class PublisherSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'name',
  1 => 'id',
);
    public static $column_hash = array (
  'name' => 1,
  'id' => 1,
);
    public static $mixin_classes = array (
);
    public static $column_names_include_virtual = array (
  0 => 'name',
  1 => 'id',
);

    const schema_class = 'tests\\PublisherSchema';
    const collection_class = 'tests\\PublisherCollection';
    const model_class = 'tests\\Publisher';
    const model_name = 'Publisher';
    const model_namespace = 'tests';
    const primary_key = 'id';
    const table = 'publishers';
    const label = 'Publisher';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
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
  'name',
);
        $this->primaryKey      = 'id';
        $this->table           = 'publishers';
        $this->modelClass      = 'tests\\Publisher';
        $this->collectionClass = 'tests\\PublisherCollection';
        $this->label           = 'Publisher';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
