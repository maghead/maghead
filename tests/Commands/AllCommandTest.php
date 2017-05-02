<?php

use CLIFramework\Testing\CommandTestCase;

/**
 * @group command
 */
class AllCommandsTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Maghead\Console;
    }

    public function testConfCommand()
    {
        $this->expectOutputRegex('/Creating symbol/');
        $this->app->run(array('maghead','use','tests/config/mysql.yml'));
    }

    public function testCommands()
    {
        $this->assertNotNull($this->app->createCommand('Maghead\Command\UseCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\SchemaCommand\BuildCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\BasedataCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\InitCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\MigrateCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\SchemaCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\DiffCommand'));
    }

    /**
     * @depends testConfCommand
     */
    public function testSchemaCommand()
    {
        $this->app->run(array('maghead','schema','build'));
    }


    /**
     * @depends testSchemaCommand
     */
    public function testListSchemaCommand()
    {
        $this->expectOutputRegex('/AuthorBooks\\\\Model\\\\AuthorSchema/');
        $this->app->run(array('maghead','schema','list'));
    }

    /**
     * @depends testSchemaCommand
     */
    public function testSqlCommand()
    {
        $this->expectOutputRegex('/Done/');
        $this->app->run(array('maghead','sql','--rebuild'));
    }

    /**
     * @depends testSqlCommand
     */
    public function testDiffCommand()
    {
        $this->expectOutputRegex('//');
        $this->app->run(array('maghead','diff'));
    }

    /**
     * @depends testSqlCommand
     */
    public function testTableCommand()
    {
        $this->expectOutputRegex('//');
        $this->app->run(array('maghead','table'));
    }


    /**
     * @depends testSqlCommand
     */
    public function testMigrateCommand()
    {
        $this->expectOutputRegex('/Found/');
        $this->app->run(array('maghead','migrate','status'));
        // $this->app->run(array('maghead','migrate','up'));
        // $this->app->run(array('maghead','migrate','down'));
    }
}
