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
        foreach( $this->arguments as $name ) {
            $argStrings[] = "\$$name";
        }
        $lines = explode("\n",$this->code);

        $lines = array_map(function($line) {
            return str_repeat(' ',8) . $line;
        },$lines);
        return str_repeat(' ',4) . $this->scope . ' function ' . $this->name . '(' . join(', ', $argStrings) . ')' . " { " . "\n"
            . join("\n",$lines)
            . "\n"
            . str_repeat(' ',4) . "}"
            . "\n"
            ;
    }
}

