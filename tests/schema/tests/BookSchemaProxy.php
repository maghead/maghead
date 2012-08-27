<?php

use LazyRecord\Schema\RuntimeSchema;

class SchemaProxy extends RuntimeSchema
{

    public function __construct()
    {
        /** columns might have closure, so it can not be const */
        $this->columns         = NULL;
        $this->columnNames     = NULL;
        $this->primaryKey      = NULL;
        $this->table           = NULL;
        $this->modelClass      = NULL;
        $this->collectionClass = NULL;
        $this->label           = NULL;
        $this->relations       = NULL;
        $this->readSourceId    = NULL;
        $this->writeSourceId    = NULL;

        parent::__construct();
    }

}
