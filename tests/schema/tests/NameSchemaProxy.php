<?php
namespace tests;

use Lazy\Schema;

class NameSchemaProxy extends Schema
{

    public function __construct()
    {
        $this->columns = array( 
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'type' => 'integer',
          'primary' => true,
        ),
    ),
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'isa' => 'str',
          'required' => true,
          'type' => 'varchar(128)',
        ),
    ),
  'address' => array( 
      'name' => 'address',
      'attributes' => array( 
          'isa' => 'str',
          'validator' => function($val,$args,$record) { 
                    if( preg_match( '/fuck/', $val ) )
                        return array( false , "Please don't" );
                    return array( true , "Message" );
                },
          'defaultBuilder' => function() { 
                    return 'Default Address';
                },
          'type' => 'varchar(256)',
        ),
    ),
  'confirmed' => array( 
      'name' => 'confirmed',
      'attributes' => array( 
          'isa' => 'bool',
        ),
    ),
);
        $this->columnNames = array( 
  'id',
  'name',
  'address',
  'confirmed',
);
        $this->primaryKey =  'id';
        $this->table = 'names';
        $this->modelClass = 'tests\\Name';
    }

}
