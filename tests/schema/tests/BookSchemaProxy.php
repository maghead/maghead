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
          'isa' => 'str',
          'unique' => true,
          'type' => 'varchar(128)',
        ),
    ),
  'subtitle' => array( 
      'name' => 'subtitle',
      'attributes' => array( 
          'isa' => 'str',
          'type' => 'varchar(256)',
        ),
    ),
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'isa' => 'str',
        ),
    ),
  'publisher_id' => array( 
      'name' => 'publisher_id',
      'attributes' => array( 
          'isa' => 'int',
        ),
    ),
  'published_at' => array( 
      'name' => 'published_at',
      'attributes' => array( 
          'isa' => 'DateTime',
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
