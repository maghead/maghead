<?php

class Column
{
    public $name;

    private $attributes = array();

    function __construct($name, & $attributes)
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    public function __isset($name)
    {
        return isset( $this->attributes[ $name ] );
    }

    public function __get($name)
    {
        return $this->attributes[$name];
    }


}



