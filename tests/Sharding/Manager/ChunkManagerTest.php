<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;
use StoreApp\Model\{Store, StoreCollection, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderCollection, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

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
        $this->mapping = $this->shardManager->getShardMapping('M_store_id');
    }

    public function testChunkDistribute()
    {
        $numberOfChunks = 32;
        $chunkManager = new ChunkManager($this->config, $this->dataSourceManager);
        $chunks = $chunkManager->distribute($this->mapping, $numberOfChunks);
        $this->assertTrue(isset($chunks[ChunkManager::HASH_RANGE]));
        $this->assertNotNull($chunks[ChunkManager::HASH_RANGE]);
        $this->assertCount($numberOfChunks, $chunks);
    }

    public function testChunkMove()
    {
        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        // Make sure all node1 orders are moved to node2
        $repo = Order::repo('node1');
        $orders = $repo->select()->fetch();
        $this->assertEquals(4, $orders->count());

        $orderIds = [];
        foreach ($orders as $o) {
            $orderIds[] = $o->getKey();
        }

        $targetNode = 'node2';
        $chunkManager = new ChunkManager($this->config, $this->dataSourceManager, $this->shardManager);
        $rets = $chunkManager->move($this->mapping, 536870912, $targetNode);
        $this->assertResultsSuccess($rets);

        $rets = $chunkManager->move($this->mapping, 1073741824, $targetNode);
        $this->assertResultsSuccess($rets);

        $rets = $chunkManager->move($this->mapping, 1610612736, $targetNode);
        $this->assertResultsSuccess($rets);

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
}
