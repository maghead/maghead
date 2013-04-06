<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class WineSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'name',
  1 => 'years',
  2 => 'category_id',
  3 => 'id',
);
    public static $column_hash = array (
  'name' => 1,
  'years' => 1,
  'category_id' => 1,
  'id' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'name',
  1 => 'years',
  2 => 'category_id',
  3 => 'id',
);

    const schema_class = 'LazyRecord\\Schema\\DynamicSchemaDeclare';
    const collection_class = 'tests\\WineCollection';
    const model_class = 'tests\\Wine';
    const primary_key = 'id';
    const table = 'wines';
    const label = 'Wine';

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
  'years' => array( 
      'name' => 'years',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
        ),
    ),
  'category_id' => array( 
      'name' => 'category_id',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
          'refer' => 'tests\\WineCategory',
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
  'years',
  'category_id',
);
        $this->primaryKey      = 'id';
        $this->table           = 'wines';
        $this->modelClass      = 'tests\\Wine';
        $this->collectionClass = 'tests\\WineCollection';
        $this->label           = 'Wine';
        $this->relations       = array( 
  'category' => \LazyRecord\Schema\Relationship::__set_state(array( 
  'data' => array( 
      'type' => 4,
      'self_schema' => 'LazyRecord\\Schema\\DynamicSchemaDeclare',
      'self_column' => 'category_id',
      'foreign_schema' => 'tests\\WineCategory',
      'foreign_column' => 'id',
    ),
)),
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
