<?php
namespace tests;

use Lazy\Schema;

class EdmSchemaProxy extends Schema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns     = array( 
  'edmNo' => array( 
      'name' => 'edmNo',
      'attributes' => array( 
          'primary' => true,
          'isa' => 'int',
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
          'isa' => 'str',
        ),
    ),
  'edmCreatedOn' => array( 
      'name' => 'edmCreatedOn',
      'attributes' => array( 
        ),
    ),
  'edmUpdatedOn' => array( 
      'name' => 'edmUpdatedOn',
      'attributes' => array( 
          'default' => array( 
              'current_timestamp',
            ),
        ),
    ),
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'isa' => 'int',
          'primary' => true,
        ),
    ),
);
        $this->columnNames = array( 
  'edmNo',
  'edmTitle',
  'edmStart',
  'edmEnd',
  'edmContent',
  'edmCreatedOn',
  'edmUpdatedOn',
  'id',
);
        $this->primaryKey  =  'id';
        $this->table       = 'Edm';
        $this->modelClass  = 'tests\\Edm';
        $this->label       = 'Edm';
    }

}
