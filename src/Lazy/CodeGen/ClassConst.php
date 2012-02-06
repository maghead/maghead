<?php
namespace LazyRecord\CodeGen;

class ClassConst
{
    public $name;
    public $value;

    public function __construct($name,$value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function __toString()
    {
        return 'const ' . $this->name . ' = ' . var_export($this->value,true) . ';';
    }
}

