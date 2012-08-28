<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

class EdmSchemaProxy extends RuntimeSchema
{

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
