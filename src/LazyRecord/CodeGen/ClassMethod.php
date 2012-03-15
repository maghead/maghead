<?php
namespace LazyRecord\CodeGen;

class ClassMethod
{
    public $name;
    public $scope = 'public';
    public $code;
    public $arguments = array();

    public function __construct($name,$arguments = array())
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function __toString()
    {
        $argStrings = array();
        foreach( $this->arguments as $name => $default ) {
            $argStrings[] = " \$$name = " . var_export( $default,true);
        }

        return $this->scope . ' function ' . $this->name . '(' . join(', ', $argStrings) . ')' . " { " . "\n"
            . $this->code
            . "\n"
            . "}"
            . "\n"
            ;
    }
}

