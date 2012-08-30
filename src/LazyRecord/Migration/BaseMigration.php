<?php
namespace LazyRecord\Migration;
use SQLBuilder\MigrationBuilder;

class BaseMigration
{
    public $driver;
    public $builder;

    public function __construct($driver) 
    {
        $this->driver = $driver;
        $this->builder = new MigrationBuilder($driver);
    }



    function __call($m,$a) {
        return call_user_func_array( array($this->builder,$m) , $a );
    }


}



