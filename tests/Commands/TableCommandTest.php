<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class TableCommandsTest extends CommandWorkFlowTestCase
{
    protected $onlyDriver = 'mysql';

    public function testTableCommand()
    {
        $this->expectOutputRegex('/Table Status Summary/');
        $this->app->run(['maghead','table','master']);
    }
}
