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
          'type' => 'text',
          'isa' => 'int',
          'primary' => true,
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
  'edmCreatedOn' => array( 
      'name' => 'edmCreatedOn',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
        ),
    ),
  'edmUpdatedOn' => array( 
      'name' => 'edmUpdatedOn',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
          'default' => array( 
              'current_timestamp',
            ),
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
);
        $this->primaryKey  =  'edmNo';
        $this->table       = 'Edm';
        $this->modelClass  = 'tests\\Edm';
        $this->label       = 'Edm';
    }

}
