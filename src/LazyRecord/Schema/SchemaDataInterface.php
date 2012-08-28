<?php
namespace LazyRecord\Schema;

interface SchemaDataInterface 
{

    public function getModelClass();
    public function getTable();
    public function getLabel();

}


