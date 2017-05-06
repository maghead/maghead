<?php

use Maghead\Testing\CommandWorkFlowTestCase;


/**
 * @group command
 */
class MetaCommandsTest extends CommandWorkFlowTestCase
{
    public $onlyDriver = 'mysql';

    public function testMetaListKeys()
    {
        $this->expectOutputRegex('/Key | Value/');
        $this->app->run(['maghead','meta', 'master']);
    }

    public function testMetaSetKeyValue()
    {
        $this->expectOutputRegex('/Setting meta foo to 1/');
        $this->app->run(['maghead','meta', 'master', 'foo', 001]);

        $this->app->run(['maghead','meta', 'master', 'foo']);
    }

    public function testMetaShowKey()
    {
        $this->expectOutputRegex('/migration = \d+/');
        $this->app->run(['maghead','meta', 'master', 'migration']);
    }
}
