<?php

use Maghead\Testing\CommandWorkFlowTestCase;

/**
 * @group command
 */
class ShardCommandsTest extends CommandWorkFlowTestCase
{
    public $onlyDriver = 'mysql';

    public function testShardMappingCreate()
    {
        $this->expectOutputRegex('/Chunk:\s*\d+/');
        $ret = $this->app->run(['maghead', 'shard',
            'mapping', 'add', '--hash', '-s', 'node1', '-s', 'node2', '--key', 'store_id', 'store_key'
        ]);
        $this->assertTrue($ret);
    }

    /**
     * @depends testShardMappingCreate
     */
    public function testShardAllocate()
    {
        $ret = $this->app->run(['maghead', 'shard', 'allocate', '--mapping', 'store_key', '--instance', 'local', 'a11']);
        $this->assertTrue($ret);
    }

    /**
     * @depends testShardAllocate
     */
    public function testShardClone()
    {
        $this->expectOutputRegex('/#...done./');
        $ret = $this->app->run(['maghead', 'shard', 'clone', '--drop-first', '--mapping', 'store_key', '--instance', 'local', 'master', 'a11']);
        $this->assertTrue($ret);
    }

    public function nodeDataProvider()
    {
        return [
            ['a11'],
            ['node1'],
            ['node2'],
            ['node3'],
        ];
    }

    /**
     * @depends testShardClone
     * @dataProvider nodeDataProvider
     */
    public function testShardPrune($node)
    {
        $ret = $this->app->run(['maghead', 'shard', 'prune', '--mapping', 'store_key', $node]);
        $this->assertTrue($ret);
    }


    /**
     * @depends testShardPrune
     */
    public function testShardMappingRemove()
    {
        $ret = $this->app->run(['maghead', 'shard', 'mapping', 'remove', 'store_key' ]);
        $this->assertTrue($ret);
    }

}
