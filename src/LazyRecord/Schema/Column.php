<?php
namespace LazyRecord\Schema;

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
        if( isset($this->attributes[$name]) )
            return $this->attributes[$name];
    }


    /** 
     * deflate value 
     **/
    public function deflate( $value )
    {
        // XXX:
        return $value;
    }

}



