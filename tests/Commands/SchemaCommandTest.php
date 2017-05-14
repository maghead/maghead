<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class SchemaCommandsTest extends CommandWorkFlowTestCase
{
    public function schemaBuildParameterDataProvider()
    {
        return [
            [['-f']],
            [['-f', 'src']],
            [['-f', 'tests/apps/AuthorBooks/Model/AddressSchema.php', 'tests/apps/AuthorBooks/Model/AuthorSchema.php']],
        ];
    }

    /**
     * @dataProvider schemaBuildParameterDataProvider
     */
    public function testSchemaBuildCommand($args)
    {
        ob_start();
        $ret = $this->app->run(array_merge(['maghead','schema','build'], $args));
        $this->assertTrue($ret);
        ob_end_clean();
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
        ob_start();
        $ret = $this->app->run(array('maghead','schema','status'));
        $this->assertTrue($ret);
        ob_end_clean();
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
