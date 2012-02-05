<?php
namespace tests;

use LazyRecord\Schema;

class MetadataMixinSchemaProxy extends Schema
{

	public function __construct()
	{
		$this->columns = array( 
  'created_on' => array( 
      'name' => 'created_on',
      'attributes' => array( 
          'isa' => 'DateTime',
          'defaultBuilder' => function() { return date('c'); },
        ),
    ),
);
		$this->columnNames = array( 
  'created_on',
);
		$this->primaryKey =  NULL;
		$this->table = 'metadata_mixins';
		$this->modelClass = 'tests\\MetadataMixin';
	}

}
