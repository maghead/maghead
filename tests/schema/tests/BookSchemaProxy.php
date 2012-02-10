<?php
namespace tests;

use Lazy\Schema;

class BookSchemaProxy extends Schema
{

    public function __construct()
    {
        $this->columns = array( 
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'primary' => true,
        ),
    ),
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
);
        $this->columnNames = array( 
  'id',
  'title',
  'subtitle',
  'description',
  'publisher_id',
  'published_at',
);
        $this->primaryKey =  'id';
        $this->table = 'books';
        $this->modelClass = 'tests\\Book';
    }

}
