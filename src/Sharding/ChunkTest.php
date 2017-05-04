<?php

namespace Maghead\Sharding;

use PHPUnit\Framework\TestCase;
use Maghead\Manager\DataSourceManager;
use Maghead\Runtime\Config\FileConfigLoader;

class ChunkTest extends TestCase
{
    public function testChunkContains()
    {
        $config = FileConfigLoader::load('tests/config/mysql.yml');
        $dsManager = new DataSourceManager($config->getDataSources());
        $chunk = new Chunk(5000, 2000, 's1', $dsManager);
        $this->assertFalse($chunk->contains(2000));
        $this->assertTrue($chunk->contains(2001));
        $this->assertTrue($chunk->contains(5000));
    }

    public function testLoadShard()
    {
        $config = FileConfigLoader::load('tests/config/mysql.yml');
        $dsManager = new DataSourceManager($config->getDataSources());
        $chunk = new Chunk(5000, 2000, 's1', $dsManager);

        $this->assertEquals('s1', $chunk->getShardId());

        $shard = $chunk->loadShard();
        $this->assertInstanceOf('Maghead\Sharding\Shard', $shard);
    }
}

