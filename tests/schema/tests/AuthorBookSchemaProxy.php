<?php
namespace tests;

use Lazy\Schema;

class AuthorBookSchemaProxy extends Schema
{

    public function __construct()
    {
        $this->columns = array( 
  'author_id' => array( 
      'name' => 'author_id',
      'attributes' => array( 
          'isa' => 'int',
        ),
    ),
  'book_id' => array( 
      'name' => 'book_id',
      'attributes' => array( 
          'isa' => 'int',
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
  'author_id',
  'book_id',
  'id',
);
        $this->primaryKey =  'id';
        $this->table = 'author_books';
        $this->modelClass = 'tests\\AuthorBook';
    }

}
