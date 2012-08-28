<?php
namespace LazyRecord\Schema;

interface SchemaDataInterface 
{
    public function getModelClass();
    public function getTable();
    public function getLabel();
    public function getRelation($relationId);
    public function getRelations();
    public function getReferenceSchemas($recursive = true);
    public function getColumns();
    public function getColumn($name);
    public function hasColumn($name);
    public function getReadSourceId();
    public function getWriteSourceId();
}


