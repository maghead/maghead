<?php

namespace Maghead\Sharding\Manager;

use Maghead\Runtime\Config\FileConfigLoader;

use Maghead\Sharding\ShardMapping;
use Maghead\Manager\DataSourceManager;

use PHPUnit\Framework\TestCase;

class ConfigManagerTest extends TestCase
{
    public function test()
    {
        $config = FileConfigLoader::load("tests/config/mysql_configserver.yml", true);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
        $manager = new ConfigManager($config);

        $ds = new DataSourceManager($config->getDataSources());

        $mapping = new ShardMapping('xxx', [ 'key' => 'store_id', 'shards' => ['node1', 'node2', 'node3'], 'hash' => true, 'chunks' => [] ], $ds);
        $ret = $manager->addShardMapping($mapping);
        $this->assertTrue($ret->isAcknowledged());
        // $shardManager = new ShardManager($config);
        // $shardManager->addShardMapping( );
    }
}

