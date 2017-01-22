<?php
use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;

class MigrateCommandsTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console;
    }

    public function testMigrateCommand()
    {
        $this->app->run(array('lazy','migrate','status'));
    }


}

