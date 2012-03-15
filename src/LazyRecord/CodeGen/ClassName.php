<?php
namespace LazyRecord\CodeGen;

class ClassName
{
    public $name;
    public $namespace;

    public function __construct($className)
    {
        // found namespace
        if( strpos( $className , '\\' ) != false ) {
            $p = explode('\\',$className);
            $this->name = end($p);
            $this->namespace = join('\\',array_splice( $p , 0 , count($p) - 1 ));
        }
        else {
            $this->name = $className;
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFullName()
    {
        if( $this->namespace )
            return '\\' .  $this->namespace . '\\' . $this->name;
        else
            return '\\' . $this->name;
    }

    public function __toString()
    {
        return $this->getFullName();
    }

}

