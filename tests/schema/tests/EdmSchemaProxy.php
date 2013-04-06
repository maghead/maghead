<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class EdmSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'edmNo',
  1 => 'edmTitle',
  2 => 'edmStart',
  3 => 'edmEnd',
  4 => 'edmContent',
  5 => 'edmUpdatedOn',
);
    public static $column_hash = array (
  'edmNo' => 1,
  'edmTitle' => 1,
  'edmStart' => 1,
  'edmEnd' => 1,
  'edmContent' => 1,
  'edmUpdatedOn' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'edmNo',
  1 => 'edmTitle',
  2 => 'edmStart',
  3 => 'edmEnd',
  4 => 'edmContent',
  5 => 'edmUpdatedOn',
);

    const schema_class = 'tests\\EdmSchema';
    const collection_class = 'tests\\EdmCollection';
    const model_class = 'tests\\Edm';
    const model_name = 'Edm';
    const primary_key = 'edmNo';
    const table = 'Edm';
    const label = 'Edm';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'edmNo' => array( 
      'name' => 'edmNo',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'primary' => true,
          'autoIncrement' => true,
        ),
    ),
  'edmTitle' => array( 
      'name' => 'edmTitle',
      'attributes' => array( 
          'type' => 'varchar(256)',
          'isa' => 'str',
          'size' => 256,
        ),
    ),
  'edmStart' => array( 
      'name' => 'edmStart',
      'attributes' => array( 
          'type' => 'date',
          'isa' => 'DateTime',
        ),
    ),
  'edmEnd' => array( 
      'name' => 'edmEnd',
      'attributes' => array( 
          'type' => 'date',
          'isa' => 'DateTime',
        ),
    ),
  'edmContent' => array( 
      'name' => 'edmContent',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
        ),
    ),
  'edmUpdatedOn' => array( 
      'name' => 'edmUpdatedOn',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
          'default' => array( 
              'current_timestamp',
            ),
        ),
    ),
);
        $this->columnNames     = array( 
  'edmNo',
  'edmTitle',
  'edmStart',
  'edmEnd',
  'edmContent',
  'edmUpdatedOn',
);
        $this->primaryKey      = 'edmNo';
        $this->table           = 'Edm';
        $this->modelClass      = 'tests\\Edm';
        $this->collectionClass = 'tests\\EdmCollection';
        $this->label           = 'Edm';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
