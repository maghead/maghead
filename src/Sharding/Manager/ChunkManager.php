<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Config;

use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use LogicException;
use Exception;
use ArrayIterator;
use Iterator;
use IteratorAggregate;

class ChunkManager
{
    protected $config;

    protected $dataSourceManager;

    protected $shardManager;

    /**
     * @var integer The default hash range 4294967296 = 2 ** 32
     */
    const HASH_RANGE = 4294967296;

    public function __construct(Config $config, DataSourceManager $dataSourceManager, ShardManager $shardManager = null)
    {
        $this->config = $config;
        $this->dataSourceManager = $dataSourceManager;
        $this->shardManager = $shardManager ?: new ShardManager($config, $dataSourceManager);
    }

    /**
     * Compute the distribution by the given shard mapping
     *
     * @return array distribution info
     */
    public function computeDistribution(ShardMapping $mapping, $numberOfChunks = 32, $range = 4294967296)
    {
        $shardIds = $mapping->getShardIds();
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil($range / $numberOfChunks));
        return [
            "hashRange" => self::HASH_RANGE,
            "numberOfChunks" => $numberOfChunks,
            "rangePerChunk"  => $rangePerChunk,
            "chunksPerShard" => $chunksPerShard,
            "shards"         => $shardIds,
        ];
    }

    /**
     * Distribute the chunks.
     *
     */
    public function distribute(ShardMapping $mapping, $numberOfChunks = 32)
    {
        $shardIds = $mapping->getShardIds();
        $chunksPerShard = intval(ceil($numberOfChunks / count($shardIds)));
        $rangePerChunk = intval(ceil(self::HASH_RANGE / $numberOfChunks));

        $chunks = [];
        $r = 0;
        foreach ($shardIds as $shardId) {
            for ($i = 0 ; $i < $chunksPerShard; $i++) {
                $r += $rangePerChunk;
                if ($r + $rangePerChunk > self::HASH_RANGE) {
                    $r = self::HASH_RANGE;
                }
                $chunks[$r] = [ 'shard' => $shardId ];
                if ($r + $rangePerChunk > self::HASH_RANGE) {
                    break;
                }
            }
        }
        $mapping->setChunks($chunks);
        return $chunks;
    }

    public function move(ShardMapping $mapping, $chunkIndex, $targetShard)
    {
        // TODO: implement chunk move
    }

    public function split(ShardMapping $mapping, $chunkIndex, $targetShard)
    {
        // TODO: implement chunk split
    }
}
