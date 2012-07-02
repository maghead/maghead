<?php
namespace LazyRecord\SqlBuilder;

class BaseBuilder
{
    public $rebuild;
    public $clean;
    public $driver;

    public function __construct($driver,$options = array())
    {
        $this->driver = $driver;
        if( isset($options['rebuild']) ) {
            $this->rebuild = $options['rebuild'];
        }
        if( isset($options['clean']) ) {
            $this->clean = $options['clean'];
        }
    }

    public function __get($name)
    {
        return $this->driver->$name;
    }

}




