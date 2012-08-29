<?php
namespace LazyRecord\Schema;
use ReflectionClass;

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

    public function getModel()
    {
        return $this->model;
    }

    public function getModelClass()
    {
        return $this->modelClass;
    }

    public function getDirectory()
    {
        $ref = new ReflectionClass($this->modelClass);
        return dirname($ref->getFilename());
    }

    public function __toString()
    {
        return $this->modelClass;
    }
}



/**
    $schema = new DynamicSchemaDeclare( $modelClass );
 */


