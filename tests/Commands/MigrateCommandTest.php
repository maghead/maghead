<?php
use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;

/**
 * @group command
 */
class MigrateCommandsTest extends CommandTestCase
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

    public function testMigrateStatusCommand()
    {
        $this->app->run(array('maghead','migrate','status'));
    }
}
