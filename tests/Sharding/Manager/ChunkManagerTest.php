<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
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
        $chunkIndex = ChunkManager::HASH_RANGE;
        $targetShard = 'shard1';
        $chunkManager = new ChunkManager($this->config, $this->dataSourceManager);
        $chunkManager->move($this->mapping, $chunkIndex, $targetShard);
    }
}
