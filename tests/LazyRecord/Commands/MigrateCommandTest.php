<?php
use CLIFramework\Testing\CommandTestCase;

class MigrateCommandsTest extends CommandTestCase
{
    public function setupApplication() {
        return new LazyRecord\Console;
    }

    public function testMigrateCommand()
    {
        $this->expectOutputRegex('/Found/');
        $this->app->run(array('lazy','migrate','status'));
        $this->app->run(array('lazy','migrate','up'));
        $this->app->run(array('lazy','migrate','down'));
    }


}

