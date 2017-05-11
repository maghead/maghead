<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class AllCommandsTest extends CommandWorkFlowTestCase
{
    public function testCreateCommandObjects()
    {
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\UseCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\SchemaCommand\BuildCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\InitCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\MigrateCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\SchemaCommand'));
        $this->assertNotNull($this->app->createCommand('Maghead\Console\Command\DiffCommand'));
    }

    public function commandsProvider()
    {
        return [
            [['maghead', 'help', 'schema']],
            [['maghead', 'help', 'schema', 'build']],
            [['maghead', 'help', 'schema', 'clean']],
            [['maghead', 'help', 'schema', 'list']],
            [['maghead', 'help', 'schema', 'status']],

            [['maghead', 'help', 'version']],

            [['maghead', 'help', 'db']],
            [['maghead', 'help', 'db', 'create']],
            [['maghead', 'help', 'db', 'recreate']],
            [['maghead', 'help', 'db', 'drop']],
            [['maghead', 'help', 'db', 'remove']],
            [['maghead', 'help', 'meta']],
            [['maghead', 'help', 'index']],
            [['maghead', 'help', 'table']],

            [['maghead', 'help', 'shard']],
            [['maghead', 'help', 'shard', 'mapping']],
            [['maghead', 'help', 'shard', 'allocate']],
            [['maghead', 'help', 'shard', 'clone']],
            [['maghead', 'help', 'shard', 'prune']],

            [['maghead', 'help', 'config']],
            [['maghead', 'help', 'config', 'upload']],
            [['maghead', 'help', 'config', 'use']],
        ];
    }


    /**
     * @dataProvider commandsProvider
     */
    public function testCommands($cmds)
    {
        ob_start();
        $ret = $this->app->run($cmds);
        $this->assertTrue($ret);
        ob_end_clean();
    }

    public function testSchemaBuildCommand()
    {
        ob_start();
        $ret = $this->app->run(['maghead', 'schema', 'build']);
        $this->assertTrue($ret);
        ob_end_clean();
    }

    /**
     * @depends testSchemaBuildCommand
     */
    public function testSqlCommand()
    {
        $this->expectOutputRegex('/Done. \d+ schema tables/');
        $ret = $this->app->run(['maghead','sql','--rebuild']);
        $this->assertTrue($ret);

    }


    /**
     * @depends testSqlCommand
     */
    public function testSeedCommand()
    {
        $this->expectOutputRegex('/Seeding/');
        $ret = $this->app->run(['maghead','seed']);
        $this->assertTrue($ret);
    }

}
