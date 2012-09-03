<?php
namespace LazyRecord\Schema;

use LazyRecord\Schema\RuntimeSchema;

class MixinSchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns         = array( 
  'id' => array( 
      'name' => 'id',
      'attributes' => array( 
          'type' => 'integer',
          'isa' => 'int',
          'primary' => true,
          'autoIncrement' => true,
        ),
    ),
);
        $this->columnNames     = array( 
  'id',
);
        $this->primaryKey      = 'id';
        $this->table           = 'mixins';
        $this->modelClass      = 'LazyRecord\\Schema\\Mixin';
        $this->collectionClass = 'LazyRecord\\Schema\\MixinCollection';
        $this->label           = 'Mixin';
        $this->relations       = array( 
);
        $this->readSourceId    = 'default';
        $this->writeSourceId    = 'default';

        parent::__construct();
    }

}
