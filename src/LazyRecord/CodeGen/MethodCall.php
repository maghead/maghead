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

    function addArgument($arg) 
    {
        $this->arguments[] = $arg;
        return $this;
    }

    function render() {
        $code = '';
        $code .= '$' . $this->objectName;
        $code .= '->' . $this->method . '(';

        $strs = array();
        foreach( $this->arguments as $arg ) {
            if( is_string($arg) && $arg[0] == '$' ) {
                $strs[] = $arg;
            } else {
                $str = var_export($arg,true);
                $strs[] = $str;
            }
        }
        $code .= join(',',$strs);
        $code .= ');';
        return $code;
    }

    function __toString() 
    {
        return $this->render();
    }
}



