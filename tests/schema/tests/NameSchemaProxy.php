<?php
namespace tests;

use Lazy\Schema\RuntimeSchema;

class NameSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns     = array( 
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'primary' => true,
          'autoIncrement' => true,
        ),
    ),
  'name' => array( 
      'name' => 'name',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'required' => true,
        ),
    ),
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
        ),
    ),
  'category_id' => array( 
      'name' => 'category_id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
        ),
    ),
  'address' => array( 
      'name' => 'address',
      'attributes' => array( 
          'type' => 'varchar(256)',
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
        ),
    ),
  'country' => array( 
      'name' => 'country',
      'attributes' => array( 
          'type' => 'text',
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
          'type' => 'boolean',
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
        $this->primaryKey  = 'id';
        $this->table       = 'names';
        $this->modelClass  = 'tests\\Name';
        $this->label       = 'Name';
        $this->relations   = array( 
);
    }

}
