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

    public function setUp()
    {
        parent::setUp();
        $db = getenv('DB') ?: 'sqlite';
        copy("tests/config/$db.yml", "tests/config/tmp.yml");
        $this->app->run(['maghead','use','tests/config/tmp.yml']);
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
     * @depends testSchemaCommand
     */
    public function testSqlCommand()
    {
        $this->expectOutputRegex('/Done. \d+ schema tables were generated into data source/');
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
