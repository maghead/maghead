<?php

use LazyRecord\Schema;

class BookSchemaProxy extends Schema {

	public function __construct()
	{
		$this->columns = array (
  'title' => 
  array (
    'name' => 'title',
    'attributes' => 
    array (
      'type' => 'varchar(256)',
      'required' => true,
      'isa' => 'string',
    ),
  ),
  'subtitle' => 
  array (
    'name' => 'subtitle',
    'attributes' => 
    array (
      'type' => 'varchar(512)',
      'default' => ' ',
      'isa' => 'string',
    ),
  ),
  'isbn' => 
  array (
    'name' => 'isbn',
    'attributes' => 
    array (
      'type' => 'varchar(128)',
      'isa' => 'string',
      'default' => '---',
    ),
  ),
  'published_on' => 
  array (
    'name' => 'published_on',
    'attributes' => 
    array (
      'isa' => 'DateTime',
    ),
  ),
  'created_on' => 
  array (
    'name' => 'created_on',
    'attributes' => 
    array (
      'isa' => 'DateTime',
    ),
  ),
);
		$this->columnNames = array (
  0 => 'title',
  1 => 'subtitle',
  2 => 'isbn',
  3 => 'published_on',
  4 => 'created_on',
);
		$this->primaryKey =  NULL;
		$this->table = 'books';
		$this->modelClass = 'Book';
	}

}
