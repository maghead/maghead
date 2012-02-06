<?php
namespace tests;

use Lazy\Schema;

class PublisherSchemaProxy extends Schema
{

	public function __construct()
	{
		$this->columns = array( 
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
          'type' => 'varchar(128)',
        ),
    ),
);
		$this->columnNames = array( 
  'id',
  'name',
);
		$this->primaryKey =  'id';
		$this->table = 'publishers';
		$this->modelClass = 'tests\\Publisher';
	}

}
