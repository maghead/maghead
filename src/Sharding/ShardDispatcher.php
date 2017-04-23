<?php

namespace Maghead\Sharding;

use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ShardManager;
use Maghead\Sharding\Hasher\Hasher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\Shard;
use Exception;

class ShardDispatcher
{
    protected $hasher;

    protected $shards;

    protected $mapping;

    public function __construct(ShardMapping $mapping, Hasher $hasher, ShardCollection $shards)
    {
        $this->mapping = $mapping;
        $this->hasher = $hasher;
        $this->shards = $shards;
    }


    /**
     * Dispatches the key and return the shard Id of the key
     *
     * @param string $key
     * @return string shard Id
     */
    public function dispatchShard($key)
    {
        $chunkId = $this->hasher->lookup($key);
        $chunk   = $this->mapping->chunks[$chunkId];
        return $chunk['shard'];
    }

    public function dispatchChunk($key)
    {
        $chunkIndex = $this->hasher->lookup($key);
        $chunk      = $this->mapping->chunks[$chunkIndex];
        return new Chunk($chunkIndex, $chunk);
    }

    /**
     * Return the shard object.
     */
    public function dispatch($key)
    {
        $chunkId = $this->hasher->lookup($key);
        $chunk   = $this->mapping->chunks[$chunkId];
        $shardId = $chunk['shard'];
        return $this->shards[$shardId];
    }
}
