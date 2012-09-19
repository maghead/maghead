<?php
namespace Todos\Model;

use LazyRecord\Schema\RuntimeSchema;

class TodoSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columnData      = array( 
  'title' => array( 
      'name' => 'title',
      'attributes' => array( 
          'type' => 'varchar(128)',
          'isa' => 'str',
          'size' => 128,
          'required' => true,
        ),
    ),
  'description' => array( 
      'name' => 'description',
      'attributes' => array( 
          'type' => 'text',
          'isa' => 'str',
        ),
    ),
  'created_on' => array( 
      'name' => 'created_on',
      'attributes' => array( 
          'type' => 'timestamp',
          'isa' => 'DateTime',
          'default' => function() {
                return date('c');
            },
        ),
    ),
);
        $this->columnNames     = array( 
  'title',
  'description',
  'created_on',
);
        $this->primaryKey      = NULL;
        $this->table           = 'todos';
        $this->modelClass      = 'Todos\\Model\\Todo';
        $this->collectionClass = 'Todos\\Model\\TodoCollection';
        $this->label           = 'Todo';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';
        parent::__construct();
    }

}
