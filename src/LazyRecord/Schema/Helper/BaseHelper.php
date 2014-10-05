<?php
namespace LazyRecord\Schema\Helper;
use LazyRecord\Schema\SchemaDeclare;
use Exception;

abstract class BaseHelper
{
    public $arguments = array();

    public $schema;

    public function __construct(SchemaDeclare $schema, $arguments = array() )
    {
        $this->schema = $schema;
        $this->arguments = $arguments;
        if (!method_exists($this,'init')) {
            throw new Exception('init method is not defined in helper');
        }
        call_user_func_array(array($this,'init'), $arguments);
    }
}

