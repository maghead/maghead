<?php
namespace tests;

use LazyRecord\Schema\RuntimeSchema;

class NameSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
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
          'typeConstraint' => true,
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
          'default' => function() { 
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
  'type' => array( 
      'name' => 'type',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
          'validValues' => function() { 
                    return array(
                        /* description => value */
                        'Type Name A' => 'type-a',
                        'Type Name B' => 'type-b',
                        'Type Name C' => 'type-c',
                    );
                },
        ),
    ),
  'confirmed' => array( 
      'name' => 'confirmed',
      'attributes' => array( 
          'type' => 'boolean',
          'isa' => 'bool',
        ),
    ),
  'date' => array( 
      'name' => 'date',
      'attributes' => array( 
          'type' => 'date',
          'isa' => 'DateTime',
          'deflator' => function($val) {
                    if( is_a( $val, 'DateTime' ) )
                        return $val->format('Y-m-d');
                    elseif( is_integer($val) ) {
                        return strftime( '%Y-%m-%d' , $val );
                    }
                    return $val;
                },
          'inflator' => function($val) { 
                    return new \DateTime( $val );
                },
        ),
    ),
);
        $this->columnNames     = array( 
  'id',
  'name',
  'description',
  'category_id',
  'address',
  'country',
  'type',
  'confirmed',
  'date',
);
        $this->primaryKey      = 'id';
        $this->table           = 'names';
        $this->modelClass      = 'tests\\Name';
        $this->collectionClass = 'tests\\NameCollection';
        $this->label           = 'Name';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
