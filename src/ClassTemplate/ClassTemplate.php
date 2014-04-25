<?php
namespace ClassTemplate;
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
    public $staticVars = array();

    /**
     * @var TemplateView object.
     */
    public $view;

    public $templateFile;
    public $templateDirs;
    public $options = array();
    public $msgIds = array();

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

    public function addConsts($array) {
        foreach( $array as $name => $value ) {
            $this->consts[] = new ClassConst($name,$value);
        }
    }

    public function addMember($name,$value,$scope = 'public')
    {
        $this->members[] = new ClassMember($name,$value,$scope);
    }

    public function addStaticVar($name, $value, $scope = 'public') 
    {
        $this->staticVars[] = new ClassStaticVariable($name, $value, $scope);
    }



    /**
     * Returns the short class name
     *
     * @return string short class name
     */
    public function getShortClassName() 
    {
        return $this->class->getName();
    }

    public function getClassName()
    {
        return $this->class->getFullName();
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

    public function addMsgId($msgId) {
        $this->msgIds[] = $msgId;
    }

    public function setMsgIds($msgIds) {
        $this->msgIds = $msgIds;
    }

}

