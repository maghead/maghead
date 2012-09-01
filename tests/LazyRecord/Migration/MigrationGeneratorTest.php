<?php

class MigrationGeneratorTest extends PHPUnit_Framework_TestCase
{

    function setUp()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array( 'dsn' => 'sqlite::memory:' ));
    }

    function tearDown()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->removeDataSource('default');
        $connectionManager->close('default');
    }


    function testGenerator()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('create table users (id integer not null primary key);');

        $generator = new \LazyRecord\Migration\MigrationGenerator('default','tests/migrations');
        ok($generator);

        is('20120901_CreateUser.php',$generator->generateFilename('CreateUser'),'20120901');

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        is('UpdateUser_1346515200', $class);
        path_ok($path);
        is('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);
    }

    function testDiffMigration() 
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $pdo = $connectionManager->getConnection('default');
        $pdo->query('create table users (id integer not null primary key);');

        $generator = new \LazyRecord\Migration\MigrationGenerator('default','tests/migrations');
        ok($generator);

        is('20120901_CreateUser.php',$generator->generateFilename('CreateUser'),'20120901');

        list($class,$path) = $generator->generate('UpdateUser','20120902');
        is('UpdateUser_1346515200', $class);
        path_ok($path);
        is('tests/migrations/20120902_UpdateUser.php',$path);
        unlink($path);

        spl_autoload_call('tests\UserSchema');

        $finder = new \LazyRecord\Schema\SchemaFinder;
        $finder->find();
        # $generator->generateWithDiff('TaskName',$finder->getSchemas() );
    }
}

