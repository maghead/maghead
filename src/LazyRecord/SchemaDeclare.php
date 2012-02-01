<?php
namespace LazyRecord;
use LazyRecord\SchemaDeclare\Column;
use Exception;

abstract class SchemaDeclare
{
    public $columns = array();


    abstract function schema();

    function __construct()
    {

    }

    public function build()
    {
        $this->schema();
    }

    protected function column($name)
    {
        if( isset($this->columns[$name]) ) {
            throw new Exception("column $name is already defined.");
        }
        return $this->columns[ $name ] = new Column( $name );
    }


}

