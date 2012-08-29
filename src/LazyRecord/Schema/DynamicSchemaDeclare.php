<?php
namespace LazyRecord\Schema;

class DynamicSchemaDeclare extends SchemaDeclare
{
    public $model;
    public $modelClass;

    public function __construct($model) 
    {
        $this->model = $model;
        $this->modelClass = get_class($model);
        $this->build();
    }

    public function build() 
    {
        $this->model->schema($this);
        parent::build();
    }

    public function getModelClass()
    {
        return $this->modelClass;
    }

    public function getDir()
    {
        $ref = new \ReflectionClass($this->modelClass);
        return dirname($ref->getFilename());
    }
}



/**
    $schema = new DynamicSchemaDeclare( $modelClass );
 */


