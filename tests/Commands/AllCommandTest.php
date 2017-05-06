<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class AllCommandsTest extends CommandWorkFlowTestCase
{
    public function testCommands()
    {
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\UseCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\SchemaCommand\BuildCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\BasedataCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\InitCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\MigrateCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\SchemaCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\DiffCommand'));
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
