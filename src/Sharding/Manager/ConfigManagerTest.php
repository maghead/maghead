<?php

namespace Maghead\Sharding\Manager;

use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Runtime\Config\MongoConfigLoader;
use Maghead\Runtime\Config\MongoConfigWriter;

use Maghead\Sharding\ShardMapping;
use Maghead\Manager\DataSourceManager;

use PHPUnit\Framework\TestCase;

class ConfigManagerTest extends TestCase
{
    protected $config;

    const TEST_CONFIG =  "tests/config/.config.tmp.yml";

    public function setUp()
    {
        copy("tests/config/mysql_configserver.yml", self::TEST_CONFIG);
        $this->config = FileConfigLoader::load(self::TEST_CONFIG, true);
    }

    protected function assertPreConditions()
    {
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $this->config);

        $ret = MongoConfigWriter::write($this->config);
        $this->assertTrue($ret->isAcknowledged(), 'config uploaded successfully');
    }

    public function tearDown()
    {
        MongoConfigWriter::remove($this->config);
        if (file_exists(self::TEST_CONFIG)) {
            unlink(self::TEST_CONFIG);
        }
    }

    public function testInstanceAddAndRemove()
    {
        $manager = new ConfigManager($this->config);
        $ret = $manager->addInstance('t11', 'mysql:host=localhost', [ 'user' => 'root', 'password' => null ]);
        $this->assertTrue($ret->isAcknowledged(), 'instance added successfully');

        $ret = $manager->removeInstance('t11');
        $this->assertTrue($ret->isAcknowledged(), 'instance remove successfully');
    }


    public function testShardMappingAddAndRemove()
    {
        $manager = new ConfigManager($this->config);

        $ds = new DataSourceManager($this->config->getDataSources());
        $mapping = new ShardMapping('xxx', [ 'key' => 'store_id', 'shards' => ['node1', 'node2', 'node3'], 'hash' => true, 'chunks' => [] ], $ds);
        $ret = $manager->setShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());

        $ret = $manager->removeShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());

        // $shardManager = new ShardManager($this->config);
        // $shardManager->setShardMapping( );
    }

    public function testShardMappingChunksUpdate()
    {
        $configManager = new ConfigManager($this->config);

        $ds = new DataSourceManager($this->config->getDataSources());

        $mapping = new ShardMapping('xxx', [ 'key' => 'store_id', 'shards' => ['node1', 'node2', 'node3'], 'hash' => true, 'chunks' => [] ], $ds);
        $ret = $configManager->setShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());

        $chunkManager = new ChunkManager($mapping);
        $chunks = $chunkManager->distribute(['node1', 'node2', 'node3'], 32);

        $ret = $configManager->updateShardMappingChunks($mapping);
        $this->assertTrue($ret->isAcknowledged());

        $newc = MongoConfigLoader::loadFromConfig($this->config);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $newc);
        $this->assertCount(32, $newc['sharding']['mappings']['xxx']['chunks']);

        $ret = $configManager->removeShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());
    }


    public function testShardMappingOneChunkUpdate()
    {
        $configManager = new ConfigManager($this->config);

        $ds = new DataSourceManager($this->config->getDataSources());

        $mapping = new ShardMapping('xxx', [ 'key' => 'store_id', 'shards' => ['node1', 'node2', 'node3'], 'hash' => true, 'chunks' => [] ], $ds);

        $chunkManager = new ChunkManager($mapping);
        $chunks = $chunkManager->distribute(['node1', 'node2', 'node3'], 4);

        // Update the shard mapping to the config server
        $ret = $configManager->setShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());

        $chunk = $mapping->loadChunk(4294967296);
        $this->assertInstanceOf('Maghead\\Sharding\\Chunk', $chunk);

        $ret = $configManager->updateShardMappingChunk($mapping, $chunk);
        $this->assertTrue($ret->isAcknowledged());

        $newc = MongoConfigLoader::loadFromConfig($this->config);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $newc);
        $this->assertCount(4, $newc['sharding']['mappings']['xxx']['chunks']);
    }

    public function testDatabaseAddAndRemove()
    {
        $manager = new ConfigManager($this->config);
        $ret = $manager->addDatabase('t1', 'sqlite::memory:');
        $this->assertTrue($ret->isAcknowledged());

        $ret = $manager->removeDatabase('t1');
        $this->assertTrue($ret->isAcknowledged());
    }
}
