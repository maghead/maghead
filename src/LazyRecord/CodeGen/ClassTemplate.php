<?php
namespace LazyRecord\CodeGen;

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

    public function render($file) 
    {
#          $view = new TemplateView( $this->getTemplatePath() );
#          $codegen = new \LazyRecord\CodeGen( $this->getTemplatePath() );
#          $codegen->stash = $args;
#          return $codegen->renderFile($file);
    }



}

