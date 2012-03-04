<?php
namespace tests;

use Lazy\Schema;

class BookSchemaProxy extends Schema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns     = array( 
  'title' => array( 
      'name' => 'title',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'unique' => true,
        ),
    ),
  'subtitle' => array( 
      'name' => 'subtitle',
      'attributes' => array( 
          'type' => 'varchar(256)',
          'isa' => 'str',
        ),
    ),
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
        ),
    ),
  'publisher_id' => array( 
      'name' => 'publisher_id',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'int',
        ),
    ),
  'published_at' => array( 
      'name' => 'published_at',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'DateTime',
        ),
    ),
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'int',
          'primary' => true,
        ),
    ),
);
        $this->columnNames = array( 
  'title',
  'subtitle',
  'description',
  'publisher_id',
  'published_at',
  'id',
);
        $this->primaryKey  =  'id';
        $this->table       = 'books';
        $this->modelClass  = 'tests\\Book';
        $this->label       = 'Book';
    }

}
