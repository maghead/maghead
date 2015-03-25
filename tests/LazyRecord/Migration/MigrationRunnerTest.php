<?php
use LazyRecord\Migration\MigrationRunner;

class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    public function testRunner()
    {
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));
        $runner = new LazyRecord\Migration\MigrationRunner('default');
        $runner->load('tests/migrations');
        /*
        $scripts = $runner->getMigrationScripts();
        $runner->runUpgrade();
        $runner->runDowngrade();
         */
    }
}

