<?php

namespace Maghead\Schema;

use ReflectionClass;

class DynamicSchemaDeclare extends DeclareSchema
{
    public $model;
    public $modelClass;

    public function __construct($model = null)
    {
        if ($model) {
            if (is_object($model)) {
                $this->model = $model;
                $this->modelClass = get_class($model);
            } elseif (is_string($model)) {
                $this->modelClass = $model;
                $this->model = new $model();
            }
            $this->model->schema($this);
            $this->build();
        }
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getModelClass()
    {
        return $this->modelClass;
    }

    public function newModel()
    {
        return new $this->getModelClass();
    }

    public function newCollection()
    {
        return new $this->getCollectionClass();
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

/*
    $schema = new DynamicSchemaDeclare( $modelClass );
 */
