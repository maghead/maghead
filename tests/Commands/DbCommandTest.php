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
        $db = getenv('DB') ?: 'sqlite';
        copy("tests/config/$db.yml", "tests/config/tmp.yml");
        $this->app->run(['maghead','use','tests/config/tmp.yml']);
        if ($db == "sqlite") {
            return $this->markTestSkipped('sqlite migration is not supported.');
        }
    }

    public function testDbList()
    {
        $this->expectOutputRegex('/master/');
        $this->app->run(['maghead','db','list']);
    }

    public function testDbCreate()
    {
        $this->expectOutputRegex('/Database testing2 is dropped successfully/');
        $this->app->run(['maghead','db','add','--user', 'root', 'testing2',  "mysql:host=localhost;dbname=testing2"]);
        $this->app->run(['maghead','db','remove','--drop', 'testing2']);
    }

    public function testDbReCreate()
    {
        $this->expectOutputRegex('/Database testing2 is dropped successfully/');
        $this->app->run(['maghead','db','add', '--create', '--user', 'root', 'testing2',  "mysql:host=localhost;dbname=testing2"]);
        $this->app->run(['maghead','db','recreate', 'testing2']);
        $this->app->run(['maghead','db','drop', 'testing2']);
    }
}
