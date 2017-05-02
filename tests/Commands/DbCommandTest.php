<?php

use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;

/**
 * @group command
 */
class DbCommandsTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console;
    }

    public function setUp()
    {
        parent::setUp();
        copy('tests/config/mysql.yml', 'tests/config/mysql.tmp.yml');
        $this->app->run(['maghead','use','tests/config/mysql.tmp.yml']);
    }

    public function testDbCommands()
    {
        $this->app->run(['maghead','db','add','--user', 'root', 'testing2',  "mysql:host=localhost;dbname=testing2"]);
        $this->app->run(['maghead','db','remove','--drop', 'testing2']);
    }
}
