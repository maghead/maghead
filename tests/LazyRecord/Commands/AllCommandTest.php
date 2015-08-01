<?php
use CLIFramework\Testing\CommandTestCase;
class AllCommandsTest extends CommandTestCase
{
    public function setupApplication() {
        return new LazyRecord\Console;
    }

    public function testConfCommand()
    {
        $this->expectOutputRegex('/Creating symbol/');
        $this->app->run(array('lazy','build-conf','db/config/database.yml'));
    }

    public function testCommands()
    {
        ok( $this->app->createCommand('LazyRecord\Command\BuildConfCommand') );
        ok( $this->app->createCommand('LazyRecord\Command\BuildSchemaCommand') );
        ok( $this->app->createCommand('LazyRecord\Command\BuildBaseDataCommand') );
        ok( $this->app->createCommand('LazyRecord\Command\InitCommand') );
        ok( $this->app->createCommand('LazyRecord\Command\MigrateCommand') );
        ok( $this->app->createCommand('LazyRecord\Command\SchemaCommand') );
        ok( $this->app->createCommand('LazyRecord\Command\DiffCommand') );
    }



    /**
     * @depends testConfCommand
     */
    public function testSchemaCommand()
    {
        $this->expectOutputRegex('/Done/');
        $this->app->run(array('lazy','schema','build'));
    }

    /**
     * @depends testSchemaCommand
     */
    public function testListSchemaCommand()
    {
        $this->expectOutputRegex('/AuthorBooks\\\\Model\\\\AuthorSchema/');
        $this->app->run(array('lazy','list-schema'));
    }

    /**
     * @depends testSchemaCommand
     */
    public function testSqlCommand()
    {
        $this->expectOutputRegex('/Done/');
        $this->app->run(array('lazy','sql','--rebuild'));
    }

    /**
     * @depends testSqlCommand
     */
    public function testDiffCommand()
    {
        $this->expectOutputRegex('//');
        $this->app->run(array('lazy','diff'));
    }

    /**
     * @depends testSqlCommand
     */
    public function testMigrateCommand()
    {
        $this->expectOutputRegex('/Found/');
        $this->app->run(array('lazy','migrate','status'));
        $this->app->run(array('lazy','migrate','up'));
        $this->app->run(array('lazy','migrate','down'));
    }


}

