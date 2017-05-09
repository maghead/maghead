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

    public function testShardMappingAddAndRemove()
    {
        $manager = new ConfigManager($this->config);

        $ds = new DataSourceManager($this->config->getDataSources());

        $mapping = new ShardMapping('xxx', [ 'key' => 'store_id', 'shards' => ['node1', 'node2', 'node3'], 'hash' => true, 'chunks' => [] ], $ds);
        $ret = $manager->addShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());

        $ret = $manager->removeShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());

        // $shardManager = new ShardManager($this->config);
        // $shardManager->addShardMapping( );
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
