<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class AllCommandsTest extends CommandWorkFlowTestCase
{
    public function testCommands()
    {
        $this->assertNotNull($this->app->createCommand('Maghead\Command\UseCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\SchemaCommand\BuildCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\BasedataCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\InitCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\MigrateCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\SchemaCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Command\DiffCommand'));
    }

    public function testSchemaCommand()
    {
        $ret = $this->app->run(['maghead', 'schema', 'build']);
        $this->assertTrue($ret);
    }

    /**
     * @depends testSchemaCommand
     */
    public function testSqlCommand()
    {
        $this->expectOutputRegex('/Done. \d+ schema tables/');
        $ret = $this->app->run(array('maghead','sql','--rebuild'));
        $this->assertTrue($ret);
    }
}
