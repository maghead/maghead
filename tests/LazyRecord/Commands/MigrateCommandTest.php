<?php
use CLIFramework\Testing\CommandTestCase;
use LazyRecord\Console;

class MigrateCommandsTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console;
    }

    public function testMigrateCommand()
    {
        $this->app->run(array('lazy','migrate','status'));
        $this->app->run(array('lazy','migrate','up'));
        $this->app->run(array('lazy','migrate','down'));
    }


}

