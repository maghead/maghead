<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class WineCategorySchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'name',
  1 => 'id',
);
    public static $column_hash = array (
  'name' => 1,
  'id' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'name',
  1 => 'id',
);

    const schema_class = 'LazyRecord\\Schema\\DynamicSchemaDeclare';
    const collection_class = 'tests\\WineCategoryCollection';
    const model_class = 'tests\\WineCategory';
    const primary_key = 'id';
    const table = 'wine_categories';
    const label = 'WineCategory';

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
        $this->table           = 'wine_categories';
        $this->modelClass      = 'tests\\WineCategory';
        $this->collectionClass = 'tests\\WineCategoryCollection';
        $this->label           = 'WineCategory';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
