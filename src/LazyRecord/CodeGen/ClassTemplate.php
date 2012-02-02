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

class ClassMember
{
	public $name;
	public $scope = 'public';
	public $value;

	function __construct($name,$value,$scope = 'public')
	{
		$this->name = $name;
		$this->value = $value;
		$this->scope = $scope;
	}


	public function __toString()
	{
		$code = $this->scope . ' $' . $this->name;
		if( $this->value ) {
			$code .= ' = ' . var_export($this->value,true) . ';';
		}
		return $code;
	}

}

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

class UseClass
{
	public $as;
	public $class;

	public function __construct($class,$as = null)
	{
		$this->class = ltrim( $class , '\\' );
		$this->as = $as ? ltrim($as,'\\') : null;
	}

	public function __toString()
	{
		$code = 'use ' . $this->class;
		if( $this->as ) {
			$code .= ' ' . $this->as . ';';
		}
		return $code;
	}

}

class ClassTemplate
{
    public $class;
    public $extends;
    public $interfaces = array();
    public $uses = array();
    public $methods = array();
    public $consts  = array();
	public $members = array();

    public function __construct($className,$namespace = null)
    {
        if( $namespace )
            $className = $namespace . '\\' . $className;
        $this->setClass($className);
    }

    public function setClass($className)
    {
        $this->class = new ClassName( $className );
    }

    public function useClass($className)
    {
        $this->uses[] = new UseClass( $className );
    }

    public function extendClass($className)
    {
        $this->extends = new ClassName($className);
    }

    public function implementClass($className)
    {
        $this->interfaces[] = new ClassName($className);
    }

    public function addMethod($scope,$methodName,$code)
    {
        $method = new ClassMethod( $methodName );
        $method->scope = $scope;
        $method->code = $code;
        $this->methods[] = $method;
    }

    public function addConst($name,$value)
    {
        $this->consts[] = new ClassConst($name,$value);
    }

	public function addMember($name,$value,$scope = 'public')
	{
		$this->members[] = new ClassMember($name,$value,$scope);
	}


}

