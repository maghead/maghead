<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

class UserSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns         = array( 
  'account' => array( 
      'name' => 'account',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'unique' => true,
        ),
    ),
  'password' => array( 
      'name' => 'password',
      'attributes' => array( 
          'type' => 'varchar(256)',
          'isa' => 'str',
        ),
    ),
);
        $this->columnNames     = array( 
  'account',
  'password',
);
        $this->primaryKey      = NULL;
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
