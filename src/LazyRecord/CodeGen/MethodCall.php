<?php
namespace LazyRecord\CodeGen;

class MethodCall
{
    public $objectName;

    public $method;

    public $arguments = array();

    function __construct($objname = 'this') {
        $this->objectName = $objname;
    }

    function method($name) 
    {
        $this->method = $name;
        return $this;
    }

    function pushArgument($arg) 
    {
        $this->arguments[] = $arg;
        return $this;
    }
}



