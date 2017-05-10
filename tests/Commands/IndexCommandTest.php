<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class IndexCommandsTest extends CommandWorkFlowTestCase
{
    public $onlyDriver = 'mysql';

    public function testIndex()
    {
        $this->expectOutputRegex('/TABLE_NAME/');
        $ret = $this->app->run(['maghead','index','master']);
    }
}
