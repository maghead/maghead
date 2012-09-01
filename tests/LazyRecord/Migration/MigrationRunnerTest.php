<?php

class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $this->expectOutputRegex('#CreateUser_1346436136#');
        $this->expectOutputRegex('#QueryOK#');
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));

        $runner = new LazyRecord\Migration\MigrationRunner('default');
        ok($runner);

        $runner->load('tests/migrations');
        $runner->runUpgrade();
    }
}

