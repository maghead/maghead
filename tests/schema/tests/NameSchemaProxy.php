<?php
namespace tests;

use LazyRecord;
use LazyRecord\Schema\RuntimeSchema;
use LazyRecord\Schema\Relationship;

class NameSchemaProxy extends RuntimeSchema
{

    public static $column_names = array (
  0 => 'id',
  1 => 'name',
  2 => 'description',
  3 => 'category_id',
  4 => 'address',
  5 => 'country',
  6 => 'type',
  7 => 'confirmed',
  8 => 'date',
);
    public static $column_hash = array (
  'id' => 1,
  'name' => 1,
  'description' => 1,
  'category_id' => 1,
  'address' => 1,
  'country' => 1,
  'type' => 1,
  'confirmed' => 1,
  'date' => 1,
);
    public static $mixin_classes = array (
);
    public static $column_names_include_virtual = array (
  0 => 'id',
  1 => 'name',
  2 => 'description',
  3 => 'category_id',
  4 => 'address',
  5 => 'country',
  6 => 'type',
  7 => 'confirmed',
  8 => 'date',
);

    const schema_class = 'tests\\NameSchema';
    const collection_class = 'tests\\NameCollection';
    const model_class = 'tests\\Name';
    const model_name = 'Name';
    const model_namespace = 'tests';
    const primary_key = 'id';
    const table = 'names';
    const label = 'Name';

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
          'size' => 128,
        ),
    ),
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
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
          'size' => 256,
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
          'type' => 'varchar(12)',
          'isa' => 'str',
          'size' => 12,
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
          'type' => 'varchar(24)',
          'isa' => 'str',
          'size' => 24,
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
                    if( is_a( $val, 'DateTime',true ) )
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
