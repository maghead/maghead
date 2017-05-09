<?php
namespace Maghead\Sharding;

use PHPUnit\Framework\TestCase;

class ShardingConfigTest extends TestCase
{
    public function test()
    {
        $config = new ShardingConfig([
            "mappings" => [
                "store_key" => [
                    "key" => "store_id",
                    "hash" => true,
                    "shards" => ["node1", "node2", "node3"],
                    "chunks" => [
                        536870912 =>  [ "shard" => "node1" ],
                        1073741824 => [ "shard" => "node1" ],
                        1610612736 => [ "shard" => "node1" ],
                        2147483648 => [ "shard" => "node2" ],
                        2684354560 => [ "shard" => "node2" ],
                        3221225472 => [ "shard" => "node2" ],
                        3758096384 => [ "shard" => "node3" ],
                        4294967296 => [ "shard" => "node3" ],
                    ]
                ],
            ]
        ]);
        $this->assertInstanceOf('Maghead\\Sharding\\ShardingConfig', $config);

        $mappingConfig = $config->getShardMapping('store_key');

        $this->assertEquals("store_id", $mappingConfig['key']);
        $this->assertEquals(true, $mappingConfig['hash']);
    }
}
