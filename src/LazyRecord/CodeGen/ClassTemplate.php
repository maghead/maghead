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


    /**
     * @var TemplateView object.
     */
    public $view;

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

        $this->view = new TemplateView($this->templateDirs);
        $this->view->class = $this;
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

    public function addMethod($scope,$methodName,$arguments = array(),$code = null)
    {
        $method = new ClassMethod( $methodName, $arguments);
        $method->scope = $scope;
        if($code)
            $method->code = $code;
        $this->methods[] = $method;
        return $method;
    }

    public function addConst($name,$value)
    {
        $this->consts[] = new ClassConst($name,$value);
    }

    public function addMember($name,$value,$scope = 'public')
    {
        $this->members[] = new ClassMember($name,$value,$scope);
    }

    public function __set($n,$v) {
        $this->view->__set($n,$v);
    }

    public function render($args = array())
    {
        foreach( $args as $n => $v ) {
            $this->view->__set($n,$v);
        }
        return $this->view->renderFile($this->templateFile);
    }

}

