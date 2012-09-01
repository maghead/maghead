<?php

class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    function testRunner()
    {
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));

        $runner = new LazyRecord\Migration\MigrationRunner('default');
        ok($runner);

        $runner->load('tests/migrations');
        return $runner;
    }


    /**
     * @depends testRunner
     */
    function testUpgrade($runner) 
    {
        $this->expectOutputRegex('#CreateUser_1346436136#');
        $this->expectOutputRegex('#QueryOK#');
        $runner->runUpgrade();
        return $runner;
    }

    /**
     * @depends testUpgrade
     */
    function testDowngrade($runner)
    {
        $this->expectOutputRegex('#QueryOK#');
        $runner->runDowngrade();
    }
}

