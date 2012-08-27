<?php
namespace LazyRecord\CodeGen;
use Exception;

class ClassTemplate
{
    public $class;
    public $extends;
    public $interfaces = array();
    public $uses = array();
    public $methods = array();
    public $consts  = array();
    public $members = array();

    public $templateFile;
    public $templateDirs;
    public $options = array();

    public function __construct($className,$options = array())
    {
        if( !isset($options['template_dirs']) ) {
            throw new Exception('template_dirs option is required.');
        }
        if( !isset($options['template']) ) {
            throw new Exception('template option is required.');
        }

        $this->options = $options;
        $this->templateFile = $options['template'];
        $this->templateDirs = $options['template_dirs'];
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

    public function render($args = array())
    {
        $view = new TemplateView($this->templateDirs);
        $view->class = $this;
        foreach( $args as $n => $v ) {
            $view->__set($n,$v);
        }
        return $view->renderFile($this->templateFile);
    }

}

