<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class DbCommandsTest extends CommandWorkFlowTestCase
{
    public $onlyDriver = 'mysql';

    public function testDbList()
    {
        $this->expectOutputRegex('/master/');
        $this->app->run(['maghead','db','list']);
    }

    /**
     * @depends testDbList
     */
    public function testDbCreate()
    {
        $this->expectOutputRegex('/Database testing2 is added successfully/');
        $ret = $this->app->run(["maghead","db","add","--user", "root", "testing2",  "mysql:host=localhost;dbname=testing2"]);
        $this->assertTrue($ret);
    }

    /**
     * @depends testDbCreate
     */
    public function testDbReCreate()
    {
        $this->expectOutputRegex('/Database testing2 is dropped successfully/');
        $ret = $this->app->run(['maghead','db','recreate', 'testing2']);
        $this->assertTrue($ret);
    }

    /**
     * @depends testDbReCreate
     */
    public function testDbDrop()
    {
        $this->expectOutputRegex('/Database testing2 is dropped successfully/');
        $ret = $this->app->run(['maghead','db','drop', 'testing2']);
        $this->assertTrue($ret);
    }

    /**
     * @depends testDbDrop
     */
    public function testDbRemove()
    {
        $this->expectOutputRegex('/Database testing2 is removed successfully/');
        $ret = $this->app->run(['maghead','db','remove', 'testing2']);
        $this->assertTrue($ret);
    }
}
