<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class UserSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'account',
  1 => 'password',
  2 => 'id',
);
    public static $column_hash = array (
  'account' => 1,
  'password' => 1,
  'id' => 1,
);
    public static $column_names_include_virtual = array (
  0 => 'account',
  1 => 'password',
  2 => 'id',
);

    const schema_class = 'tests\\UserSchema';
    const collection_class = 'tests\\UserCollection';
    const model_class = 'tests\\User';
    const model_name = 'User';
    const primary_key = 'id';
    const table = 'users';
    const label = 'User';

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'account' => array( 
      'name' => 'account',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'unique' => true,
          'size' => 128,
        ),
    ),
  'password' => array( 
      'name' => 'password',
      'attributes' => array( 
          'type' => 'varchar(256)',
          'isa' => 'str',
          'size' => 256,
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
  'account',
  'password',
);
        $this->primaryKey      = 'id';
        $this->table           = 'users';
        $this->modelClass      = 'tests\\User';
        $this->collectionClass = 'tests\\UserCollection';
        $this->label           = 'User';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
