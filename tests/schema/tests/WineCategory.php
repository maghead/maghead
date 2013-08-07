<?php
namespace tests;
use LazyRecord\BaseModel;

class WineCategory extends BaseModel
{
    public function dataLabel()
    {
        return $this->name;
    }
}
