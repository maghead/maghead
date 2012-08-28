<?php
namespace LazyRecord\Schema;

class DynamicSchemaDeclare extends SchemaDeclare
{
    public $modelClass;

    public function __construct($modelClass) 
    {
        $this->modelClass = $modelClass;
        $this->build();
    }

    public function build() 
    {
        $model = new $this->modelClass;
        $model->schema($this);
        parent::build();
    }

    public function getModelClass()
    {
        return $this->modelClass;
    }
}



/**
    $schema = new DynamicSchemaDeclare( $modelClass );
 */


