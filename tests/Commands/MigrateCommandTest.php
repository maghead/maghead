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
        copy('tests/config/mysql.yml', 'tests/config/mysql.tmp.yml');
        $this->app->run(['maghead','use','tests/config/mysql.tmp.yml']);
    }

    public function testMigrateStatusCommand()
    {
        $this->app->run(array('maghead','migrate','status'));
    }
}
