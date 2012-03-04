<?php
namespace tests;

use Lazy\Schema;

class NameSchemaProxy extends Schema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns     = array( 
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
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'isa' => 'str',
          'type' => 'varchar(128)',
        ),
    ),
  'category_id' => array( 
      'name' => 'category_id',
      'attributes' => array( 
          'isa' => 'int',
        ),
    ),
  'address' => array( 
      'name' => 'address',
      'attributes' => array( 
          'isa' => 'str',
          'validator' => function($val,$args,$record) { 
                    if( preg_match( '/fuck/', $val ) )
                        return array( false , "Please don't" );
                    return array( true , "Good" );
                },
          'filter' => function($val,$args,$record)  { 
					return str_replace( 'John' , 'XXXX' , $val );
				},
          'defaultBuilder' => function() { 
                    return 'Default Address';
                },
          'type' => 'varchar(256)',
        ),
    ),
  'country' => array( 
      'name' => 'country',
      'attributes' => array( 
          'isa' => 'str',
          'required' => true,
          'validValues' => array( 
              'Taiwan',
              'Taipei',
              'Tokyo',
            ),
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
  'description',
  'category_id',
  'address',
  'country',
  'confirmed',
);
        $this->primaryKey  =  'id';
        $this->table       = 'names';
        $this->modelClass  = 'tests\\Name';
        $this->label       = 'Name';
    }

}
