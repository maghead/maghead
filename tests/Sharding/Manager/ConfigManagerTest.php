<?php
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

/**
 * @group sharding
 */
class ShardConfigManagerTest extends StoreTestCase
{
    protected $shardManager;

    protected $mapping;

    const TEST_CONFIG = 'tests/config/.database.config.yml';

    public function setUp()
    {
        parent::setUp();
        $this->shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $this->mapping = $this->shardManager->getShardMapping('M_store_id');

        if (file_exists(self::TEST_CONFIG)) {
            unlink(self::TEST_CONFIG);
        }
    }

    public function tearDown()
    {
        if (file_exists(self::TEST_CONFIG)) {
            unlink(self::TEST_CONFIG);
        }
    }

    public function testAddShardMapping()
    {
        $numberOfChunks = 8;
        $chunkManager = new ChunkManager($this->config, $this->dataSourceManager);
        $chunks = $chunkManager->distribute($this->mapping, $numberOfChunks);
        $this->assertTrue(isset($chunks[ChunkManager::HASH_RANGE]));
        $this->assertNotNull($chunks[ChunkManager::HASH_RANGE]);
        $this->assertCount($numberOfChunks, $chunks);

        $configManager = new ConfigManager($this->config);
        $configManager->addShardMapping($this->mapping);
        $ret = $configManager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
    }
}
