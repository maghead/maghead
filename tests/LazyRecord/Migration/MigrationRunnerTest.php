<?php

class MigrationRunnerTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        ob_start();
        $connm = LazyRecord\ConnectionManager::getInstance();
        $connm->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));

        $runner = new LazyRecord\Migration\MigrationRunner('default');
        ok($runner);

        $runner->load('tests/migrations');
        $runner->runUpgrade();

        ob_end_clean();
    }
}

