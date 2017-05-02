<?php

use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;

/**
 * @group command
 */
class SchemaCommandsTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console;
    }

    public function setUp()
    {
        parent::setUp();
        $db = getenv('DB') ?: 'sqlite';
        copy("tests/config/$db.yml", "tests/config/tmp.yml");
        $this->app->run(['maghead','use','tests/config/tmp.yml']);
    }

    public function testSchemaBuildCommand()
    {
        $ret = $this->app->run(array('maghead','schema','build'));
        $this->assertTrue($ret);
    }

    /**
     * @depends testSchemaBuildCommand
     */
    public function testSchemaListCommand()
    {
        $this->expectOutputRegex('/AuthorBooks\\\\Model\\\\AuthorSchema/');
        $this->app->run(array('maghead','schema','list'));
    }

    /**
     * @depends testSchemaListCommand
     */
    public function testSchemaStatusCommand()
    {
        $ret = $this->app->run(array('maghead','schema','status'));
        $this->assertTrue($ret);
    }

    /**
     * @depends testSchemaListCommand
     */
    public function testSchemaCleanCommand()
    {
        $ret = $this->app->run(array('maghead','schema','clean'));
        $this->assertTrue($ret);
    }
}
