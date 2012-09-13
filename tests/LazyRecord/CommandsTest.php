<?php

class CommandsTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $app = new LazyRecord\Console;
        ok($app);
        ok( $app->createCommand('LazyRecord\Command\BuildConfCommand') );
        ok( $app->createCommand('LazyRecord\Command\BuildSchemaCommand') );
        ok( $app->createCommand('LazyRecord\Command\BuildBaseDataCommand') );
        ok( $app->createCommand('LazyRecord\Command\PrepareCommand') );
        ok( $app->createCommand('LazyRecord\Command\InitCommand') );
        ok( $app->createCommand('LazyRecord\Command\CreateDBCommand') );
        ok( $app->createCommand('LazyRecord\Command\MigrateCommand') );
        ok( $app->createCommand('LazyRecord\Command\SchemaCommand') );
        ok( $app->createCommand('LazyRecord\Command\DiffCommand') );
    }

    function testConfCommand()
    {
        $this->expectOutputRegex('/Making link/');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','build-conf'));
    }


    /**
     * @depends testConfCommand
     */
    function testSchemaCommand()
    {
        $this->expectOutputRegex('/Done/');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','build-schema'));
    }


    /**
     * @depends testSchemaCommand
     */
    function testSqlCommand()
    {
        $this->expectOutputRegex('/Done/');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','build-sql','--rebuild'));
    }

}

