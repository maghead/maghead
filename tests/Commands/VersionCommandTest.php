<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class VersionCommandTest extends CommandWorkFlowTestCase
{
    public function testDbList()
    {
        $this->expectOutputRegex('/master database version/');
        $this->app->run(['maghead','version']);
    }
}
