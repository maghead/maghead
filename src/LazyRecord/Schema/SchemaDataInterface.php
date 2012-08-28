<?php
namespace LazyRecord\Schema;

interface SchemaDataInterface 
{

    public function getTable();

    public function getLabel();

    public function getReferenceSchemas($recursive = true);

    public function getColumns($includeVirtual = false);

    public function getColumnNames($includeVirtual = false);

    public function getColumn($name);

    public function hasColumn($name);

    public function getModelClass();

    public function getReadSourceId();

    public function getWriteSourceId();

    public function getRelation($relationId);

    public function getRelations();
}


