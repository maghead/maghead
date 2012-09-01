<?php

class MigrationGeneratorTest extends PHPUnit_Framework_TestCase
{
    function testGenerator()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('create table users (id integer not null primary key);');

        $generator = new \LazyRecord\Migration\MigrationGenerator('tests/migrations');
        ok($generator);

        is('20120901_CreateUser.php',$generator->generateFilename('CreateUser'),'20120901');

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        is('UpdateUser_1346515200', $class);
        path_ok($path);
        is('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }

    function testDiffMigration() 
    {
        $this->expectOutputRegex('#DiffMigration_1325347200#');
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 
            'driver' => 'mysql',
            'database' => 'testing',
            'user' => 'testing',
            'pass' => 'testing',
        ));

        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('drop table if exists users');
        $pdo->query('drop table if exists test');
        $pdo->query('create table users (account varchar(128) unique);');

        if( ! file_exists('tests/migrations_testing') )
            mkdir('tests/migrations_testing');
        $generator = new \LazyRecord\Migration\MigrationGenerator('tests/migrations_testing');
        ok($generator);

        spl_autoload_call('tests\UserSchema');
        $finder = new \LazyRecord\Schema\SchemaFinder;
        $finder->find();
        list($class,$path) = $generator->generateWithDiff('DiffMigration','default',$finder->getSchemas(),'20120101');
        ok($class);
        ok($path);
        require_once $path;
        path_ok($path);
        class_ok($class);

        // run migration
        $runner = new LazyRecord\Migration\MigrationRunner('default');
        ok($runner);
        $runner->load('tests/migrations_testing');
        $runner->runUpgrade();

        # echo file_get_contents($path);
        unlink($path);

        $pdo->query('drop table if exists users');
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }
}

