<?php
use Maghead\Testing\ModelTestCase;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\ShardDispatcher;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema};
use StoreApp\StoreTestCase;

/**
 * @group sharding
 * @group manager
 */
class ShardManagerTest extends StoreTestCase
{
    protected $freeConnections = false;

    public function testGetMappingById()
    {
        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $mapping = $shardManager->getShardMapping('M_store_id');
        $this->assertNotEmpty($mapping);
    }

    public function testGetShards()
    {
        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $shards = $shardManager->getShardsOf('M_store_id');
        $this->assertInstanceOf('Maghead\\Sharding\\ShardCollection', $shards);
        $this->assertNotEmpty($shards);
    }

    public function testCreateShardDispatcherFromShardCollection()
    {
        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $shards = $shardManager->getShardsOf('M_store_id');
        $dispatcher = $shards->createDispatcher();
        $this->assertNotNull($dispatcher);
    }

    public function testCreateShardDispatcher()
    {
        $shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $mapping = $shardManager->getShardMapping('M_store_id');
        $shards = $shardManager->getShardsOf('M_store_id');
        $dispatcher = new ShardDispatcher($mapping, $shards);
        $this->assertNotNull($dispatcher);
        return $dispatcher;
    }

    /**
     * @depends testCreateShardDispatcher
     */
    public function testDispatcherCreateRepo($dispatcher)
    {
        $shard = $dispatcher->dispatch('3d221024-eafd-11e6-a53b-3c15c2cb5a5a');
        $this->assertInstanceOf('Maghead\\Sharding\\Shard', $shard);

        $repo = $shard->createRepo('StoreApp\\Model\\StoreRepo');
        $this->assertInstanceOf('Maghead\\Runtime\\BaseRepo', $repo);
        $this->assertInstanceOf('StoreApp\\Model\\StoreRepo', $repo);
        return $repo;
    }

    /**
     * @depends testCreateShardDispatcher
     */
    public function testDispatchLoadChunk($dispatcher)
    {
        $chunk = $dispatcher->dispatchChunk('3d221024-eafd-11e6-a53b-3c15c2cb5a5a');
        $this->assertInstanceOf('Maghead\\Sharding\\Chunk', $chunk);
        $this->assertEquals(4294967296, $chunk->index);
        $this->assertEquals(3758096384, $chunk->from);
        $this->assertSame([
            'shard' => 'node3',
        ], $chunk->config);
    }

    /**
     * @depends testDispatcherCreateRepo
     */
    public function testWriteRepo($repo)
    {
        $ret = $repo->create([ 'name' => 'My Store', 'code' => 'MS001' ]);
        $this->assertResultSuccess($ret);
    }

    public function testRequiredField()
    {
        $ret = Store::create([ 'name' => 'testapp2', 'code' => 'testapp2' ]);
        $this->assertResultSuccess($ret);
    }

    public function testCreateWithRequiredFieldNull()
    {
        $ret = Store::create([ 'name' => 'testapp', 'code' => null ]);
        $this->assertResultFail($ret);
    }

    public function testUpdateWithRequiredFieldNull()
    {
        $store = Store::createAndLoad([ 'name' => 'testapp', 'code' => 'testapp' ]);
        $this->assertNotFalse($store);

        $ret = $store->update([ 'name' => 'testapp', 'code' => null ]);
        $this->assertResultFail($ret);

        $ret = $store->update([ 'name' => 'testapp 2' ]);
        $this->assertResultSuccess($ret);
        $this->assertEquals('testapp 2', $store->name);
    }
}
