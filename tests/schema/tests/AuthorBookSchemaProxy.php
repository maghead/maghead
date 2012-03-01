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
);
        $this->columnNames = array( 
  'author_id',
  'book_id',
);
        $this->primaryKey =  NULL;
        $this->table = 'author_books';
        $this->modelClass = 'tests\\AuthorBook';
    }

}
