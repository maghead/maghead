<?php
namespace LazyRecord\Schema;
use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;

class SchemaCollection implements IteratorAggregate
{
    protected $schemas = array();

    public function __construct(array $args) 
    {
        $this->schemas = $args;
        $this->evaluate();
    }

    public function evaluate() 
    {
        $this->schemas = array_map(function($a) {
            return is_string($a) ? new $a : $a;
        }, $this->schemas);
    }

    public function getSchemas() 
    {
        return $this->schemas;
    }

    public function getClasses() 
    {
        return array_map(function($a) { return get_class($a); }, $this->schemas);
    }


    static public function evaluateArray(array $classes) 
    {
        $schemas = array_map(function($a) {
            return is_string($a) ? new $a : $a;
        }, $classes);
        return new self($schemas);
    }



    public function getIterator()
    {
        return new ArrayIterator($this->schemas);
    }
}



