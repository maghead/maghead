<?php
use LazyRecord\Migration\MigrationRunner;

/**
 * @group migration
 */
class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    // FIXME
    public function testRunner()
    {
        /*
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('sqlite',array( 'dsn' => 'sqlite::memory:' ));
        $runner = new MigrationRunner('mysql');
        $runner->load('tests/migrations');
        $runner->runUpgradeAutomatically();
        */
    }
}

