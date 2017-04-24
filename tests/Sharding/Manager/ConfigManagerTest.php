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

    protected $onlyDriver = 'mysql';

    const TEST_CONFIG = 'tests/config/.database.config.yml';

    public function setUp()
    {
        parent::setUp();
        $this->shardManager = new ShardManager($this->config, $this->dataSourceManager);
        $this->mapping = $this->shardManager->loadShardMapping('M_store_id');

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
        $chunkManager = new ChunkManager($this->mapping);
        $chunks = $chunkManager->distribute($numberOfChunks);
        $this->assertTrue(isset($chunks[ChunkManager::HASH_RANGE]));
        $this->assertNotNull($chunks[ChunkManager::HASH_RANGE]);
        $this->assertCount($numberOfChunks, $chunks);

        $configManager = new ConfigManager($this->config);
        $configManager->addShardMapping($this->mapping);
        $ret = $configManager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testAddShardMapping.expected');
        $this->assertFileEquals('tests/fixtures/config/testAddShardMapping.expected', self::TEST_CONFIG);
    }
}
