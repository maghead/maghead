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

    public function testMigrateStatusCommand()
    {
        $this->app->run(array('maghead','migrate','status'));
    }
}
