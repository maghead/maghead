<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;
use Maghead\Sharding\Chunk;
use StoreApp\Model\{Store, StoreCollection, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderCollection, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;
use Maghead\Schema\SchemaUtils;

/**
 * @group sharding
 */
class ChunkManagerTest extends StoreTestCase
{
    protected $shardManager;

    protected $mapping;

    public function setUp()
    {
        parent::setUp();
        $this->shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $this->mapping = $this->shardManager->loadShardMapping('M_store_id');
    }

    public function testChunkDistribute()
    {
        $numberOfChunks = 32;
        $shardIds = $this->mapping->getShardIds();
        $chunkManager = new ChunkManager($this->mapping);
        $chunks = $chunkManager->distribute($shardIds, $numberOfChunks);
        $this->assertTrue(isset($chunks[Chunk::MAX_KEY]));
        $this->assertNotNull($chunks[Chunk::MAX_KEY]);
        $this->assertCount($numberOfChunks, $chunks);
    }

    public function testChunkMove()
    {
        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        // Make sure all node1 orders are moved to node2
        $repo = Order::repo('node1');
        $orders = $repo->select()->fetch();
        $this->assertEquals(6, $orders->count());

        $orderIds = [];
        foreach ($orders as $o) {
            $orderIds[] = $o->getKey();
        }

        $schemas = SchemaUtils::findSchemasByConfig($this->config);

        $targetNode = 'node2';
        $chunkManager = new ChunkManager($this->mapping);

        $c = $this->mapping->loadChunk(536870912);

        $shards = $this->mapping->loadShardCollection();
        $dstShard = $shards[$targetNode];

        $rets = $chunkManager->move($c, $dstShard, $schemas);
        $this->assertCount(6, $rets);
        $this->assertResultsSuccess($rets);

        $c = $this->mapping->loadChunk(1073741824);

        $rets = $chunkManager->move($c, $dstShard, $schemas);
        $this->assertResultsSuccess($rets);
        $this->assertCount(0, $rets);

        $c = $this->mapping->loadChunk(1610612736);
        $rets = $chunkManager->move($c, $dstShard, $schemas);
        $this->assertResultsSuccess($rets);
        $this->assertCount(0, $rets);

        // Make sure all node1 orders are moved to node2
        $repo = Order::repo('node1');
        $orders = $repo->select()->fetch();
        $this->assertEquals(0, $orders->count());

        $repo2 = Order::repo('node2');
        foreach ($orderIds as $oId) {
            $o = $repo2->findByPrimaryKey($oId);
            $this->assertNotFalse($o);
            $this->assertInstanceOf('Maghead\\Runtime\\BaseModel', $o);
        }
    }

    public function testChunkSplit()
    {
        $chunkManager = new ChunkManager($this->mapping);
        $this->assertCount(8, $this->mapping->chunks);

        $c = $this->mapping->loadChunk(1073741824);

        $indexes = $chunkManager->split($c);
        $this->assertCount(1, $indexes);
        $this->assertEquals(805306368, $indexes[0]);
        $this->assertCount(9, $this->mapping->chunks);
    }

    public function testChunkClone()
    {
        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        $chunkManager = new ChunkManager($this->mapping);
        $this->assertCount(8, $this->mapping->chunks);

        $schemas = SchemaUtils::findSchemasByConfig($this->config);

        $c = $this->mapping->loadChunk(536870912);

        $shards = $this->mapping->loadShardCollection();

        $rets = $chunkManager->clone($c, $shards['node2'], $schemas);
        $this->assertResultsSuccess($rets);
    }

    public function testChunkSplitAndMove()
    {
        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        $repo = Order::repo('node1');
        $orders = $repo->select()->fetch();
        $this->assertCount(6, $orders);

        $chunkManager = new ChunkManager($this->mapping);
        $this->assertCount(8, $this->mapping->chunks);


        $c = $this->mapping->loadChunk(536870912);
        $indexes = $chunkManager->split($c, 12);
        $this->assertCount(11, $indexes);

        // shard keys: 2, 6
        $shardKeys = [450215437, 498629140];
        $this->assertSame([
            492131673 => [ 450215437 ],
            536870912 => [ 498629140 ],
        ], $this->mapping->partition($shardKeys));
        $this->assertCount(19, $this->mapping->chunks);

        $shards = $this->mapping->loadShardCollection();

        $schemas = SchemaUtils::findSchemasByConfig($this->config);
        foreach ($indexes as $index) {
            $c = $this->mapping->loadChunk($index);
            $rets = $chunkManager->move($c, $shards['node3'], $schemas);
            $this->assertResultsSuccess($rets);
        }
    }


    public function testChunkSplitAndMigrate()
    {
        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        $repo = Order::repo('node1');
        $orders = $repo->select()->fetch();
        $this->assertCount(6, $orders);

        $chunkManager = new ChunkManager($this->mapping);
        $this->assertCount(8, $this->mapping->chunks);


        $c = $this->mapping->loadChunk(536870912);
        $indexes = $chunkManager->split($c, 12);
        $this->assertCount(11, $indexes);

        // shard keys: 2, 6
        $shardKeys = [450215437, 498629140];
        $this->assertSame([
            492131673 => [ 450215437 ],
            536870912 => [ 498629140 ],
        ], $this->mapping->partition($shardKeys));
        $this->assertCount(19, $this->mapping->chunks);

        $shards = $this->mapping->loadShardCollection();

        $schemas = SchemaUtils::findSchemasByConfig($this->config);
        foreach ($indexes as $index) {
            $c = $this->mapping->loadChunk($index);
            $rets = $chunkManager->migrate($c, $shards['node3'], $schemas);
            $this->assertResultsSuccess($rets);
        }
    }


}
