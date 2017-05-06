<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class DiffCommandTest extends CommandWorkFlowTestCase
{
    public function testDiffCommand()
    {
        $this->expectOutputRegex('/Performing comparison.../');
        $ret = $this->app->run(array('maghead','diff'));
        $this->assertTrue($ret);
    }
}
