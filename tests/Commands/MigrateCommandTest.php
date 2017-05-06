<?php
use Maghead\Testing\CommandWorkFlowTestCase;
use Maghead\Console;

/**
 * @group command
 */
class MigrateCommandsTest extends CommandWorkFlowTestCase
{
    public $onlyDriver = ['mysql', 'pgsql'];

    public function testMigrateStatus()
    {
        $this->expectOutputRegex("/Found \\d+ migration script to be executed/");
        $this->app->run(array('maghead','migrate','status'));
    }

    /**
     * @depends testMigrateStatus
     */
    public function testMigrateUp()
    {
        $this->expectOutputRegex('/Performing upgrade on node master/');
        $this->app->run(array('maghead','migrate','up', 'master'));
    }

    /**
     * @depends testMigrateUp
     */
    public function testMigrateDown()
    {
        $this->expectOutputRegex('/Performing downgrade on node master/');
        $this->app->run(array('maghead','migrate','down','master'));
    }

    /**
     * @depends testMigrateDown
     */
    public function testMigrateAuto()
    {
        $this->expectOutputRegex('/Performing automatic upgrade over data source: master/');
        $this->app->run(['maghead','migrate','auto','master']);
    }
}
