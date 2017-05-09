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

    public function setUp()
    {
        $this->config = FileConfigLoader::load("tests/config/mysql_configserver.yml", true);
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
    }

    public function test()
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
}

