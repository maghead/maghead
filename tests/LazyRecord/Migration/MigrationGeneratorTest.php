<?php

class MigrationGeneratorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $connectionManager = \LazyRecord\ConnectionManager::getInstance();
        $connectionManager->addDataSource('default',array(
            'dsn' => 'sqlite::memory:'
        ));

        $generator = new LazyRecord\Migration\MigrationGenerator('default','tests/migration');
        ok($generator);

        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->find();
        $generator->generate( $finder->getSchemas() );

        $connectionManager->removeDataSource('default');
    }
}

