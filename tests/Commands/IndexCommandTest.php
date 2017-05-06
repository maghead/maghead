<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class IndexCommandsTest extends CommandWorkFlowTestCase
{
    public $onlyDriver = 'mysql';

    public function setUp()
    {
        if (getenv('TRAVIS')) {
            // FIXME: FAILS ON TRAVIS-CI, innodb
            return $this->markTestSkipped('innodb is not supported on Travis-CI');
        }
        parent::setUp();
    }

    public function testIndex()
    {
        $this->expectOutputRegex('/TABLE_NAME/');
        $ret = $this->app->run(['maghead','index','master']);
    }
}
