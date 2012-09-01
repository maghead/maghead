<?php

class MigrationGeneratorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array(
            'dsn' => 'sqlite::memory:'
        ));

        $pdo = $connectionManager->getConnection('default');
        $pdo->query('create table users (id integer not null primary key);');

        $generator = new \LazyRecord\Migration\MigrationGenerator('default','tests/migration');
        ok($generator);

        spl_autoload_call('tests\UserSchema');

        $finder = new \LazyRecord\Schema\SchemaFinder;
        $finder->find();
        $generator->generate( $finder->getSchemas() );

        $connectionManager->removeDataSource('default');
    }
}

