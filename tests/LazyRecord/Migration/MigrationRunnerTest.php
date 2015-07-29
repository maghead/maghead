<?php
use LazyRecord\Migration\MigrationRunner;

class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    public function testRunner()
    {
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('sqlite',array( 'dsn' => 'sqlite::memory:' ));
        $runner = new LazyRecord\Migration\MigrationRunner('sqlite');
        $runner->load('tests/migrations');
        $runner->runUpgradeAutomatically();
    }
}

