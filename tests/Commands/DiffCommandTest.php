<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class DiffCommandTest extends CommandWorkFlowTestCase
{
    public function testDiffCommand()
    {
        $ret = $this->app->run(array('maghead','diff'));
        $this->assertTrue($ret);
    }
}
