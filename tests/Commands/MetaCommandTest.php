<?php

use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;

/**
 * @group command
 */
class MetaCommandsTest extends CommandTestCase
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

    public function testMetaListKeys()
    {
        $this->expectOutputRegex('/Key | Value/');
        $this->app->run(['maghead','meta', 'master']);
    }

    public function testMetaSetKeyValue()
    {
        $this->expectOutputRegex('/Setting meta foo to 1/');
        $this->app->run(['maghead','meta', 'master', 'foo', 001]);

        $this->app->run(['maghead','meta', 'master', 'foo']);
    }

    public function testMetaShowKey()
    {
        $this->expectOutputRegex('/migration = \d+/');
        $this->app->run(['maghead','meta', 'master', 'migration']);
    }
}
