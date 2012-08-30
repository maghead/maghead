<?php

class FooMigration extends LazyRecord\BaseMigration 
{

    public function upgrade() 
    {
        $this->createTable();
    }


}

class BaseMigrationTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        
    }
}

