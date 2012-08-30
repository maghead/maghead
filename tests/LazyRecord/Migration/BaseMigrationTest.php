<?php

class FooMigration extends LazyRecord\Migration\BaseMigration 
{
    public function upgrade() 
    {
    }
}

class BaseMigrationTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('default',array(
            'dsn' => 'sqlite::memory:'
        ));

        $migration = new FooMigration('default');
        ok($migration);

        $connm->removeDataSource('default');
    }
}

