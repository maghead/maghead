<?php
namespace LazyRecord\SqlBuilder;

class BaseBuilder
{
    public $parent;

    public function __construct( $parentBuilder )
    {
        $this->parent = $parentBuilder;
    }

    public function __get($name)
    {
        return $this->parent->driver->$name;
    }

}




