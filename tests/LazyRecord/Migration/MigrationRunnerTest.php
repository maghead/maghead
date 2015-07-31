<?php
use LazyRecord\Migration\MigrationRunner;

class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    public function testRunner()
    {
        /*
         * FIXME:
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('sqlite',array( 'dsn' => 'sqlite::memory:' ));
        $runner = new MigrationRunner('mysql');
        $runner->load('tests/migrations');
        $runner->runUpgradeAutomatically();
         */
    }
}

