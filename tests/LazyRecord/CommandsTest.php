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
    function testListSchemaCommand()
    {
        $this->expectOutputRegex('/AuthorBooks\\\\Model\\\\AuthorSchema/');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','list-schema'));
    }

    /**
     * @depends testSchemaCommand
     */
    function testSqlCommand()
    {
        $this->expectOutputRegex('/Done/');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','sql','--rebuild'));
    }

    /**
     * @depends testSqlCommand
     */
    function testDiffCommand()
    {
        $this->expectOutputRegex('//');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','diff'));
    }

    /**
     * @depends testSqlCommand
     */
    function testMigrateCommand()
    {
        $this->expectOutputRegex('/Found/');
        $app = new LazyRecord\Console;
        $app->run(array('lazy','migrate','--status'));

        $app->run(array('lazy','migrate','--up'));
        $app->run(array('lazy','migrate','--down'));
    }


}

