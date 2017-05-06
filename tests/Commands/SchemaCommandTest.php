<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class SchemaCommandsTest extends CommandWorkFlowTestCase
{
    public function testSchemaBuildCommand()
    {
        $ret = $this->app->run(array('maghead','schema','build','-f'));
        $this->assertTrue($ret);
    }

    /**
     * @depends testSchemaBuildCommand
     */
    public function testSchemaListCommand()
    {
        $this->expectOutputRegex('/AuthorBooks\\\\Model\\\\AuthorSchema/');
        $this->app->run(array('maghead','schema','list'));
    }

    /**
     * @depends testSchemaListCommand
     */
    public function testSchemaStatusCommand()
    {
        // $this->expectOutputRegex('/up-to-date/');
        $ret = $this->app->run(array('maghead','schema','status'));
        $this->assertTrue($ret);
    }

    /**
     * @depends testSchemaListCommand
     */
    public function testSchemaCleanCommand()
    {
        $this->expectOutputRegex('/Cleaning schema/');
        $ret = $this->app->run(array('maghead','schema','clean'));
        $this->assertTrue($ret);
    }
}
