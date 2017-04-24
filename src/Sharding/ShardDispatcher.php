<?php

namespace Maghead\Sharding;

use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ShardManager;
use Maghead\Sharding\Hasher\Hasher;
use Maghead\Sharding\Hasher\FastHasher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\Shard;
use Exception;

class ShardDispatcher
{
    protected $hasher;

    protected $shards;

    protected $mapping;

    public function __construct(ShardMapping $mapping, ShardCollection $shards, Hasher $hasher = null)
    {
        $this->mapping = $mapping;
        $this->hasher = $hasher ?: new FastHasher($mapping);
        $this->shards = $shards;
    }

    public function getHasher()
    {
        return $this->hasher;
    }


    public function hash($key)
    {
        return $this->hasher->lookup($key);
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
        return $this->mapping->loadChunk($chunkIndex);
    }

    /**
     * Dispatch the key and return the shard object.
     *
     * @return Shard
     */
    public function dispatch($key)
    {
        $chunkId = $this->hasher->lookup($key);
        $chunk   = $this->mapping->chunks[$chunkId];
        return $this->shards[$chunk['shard']];
    }

    /**
     * Find the keys that doens't belong to the shard.
     *
     * @param string $shardId the shard ID
     * @param array $keys the keys in the chunk or shard.
     * @return array keys will need to migrate.
     */
    public function filterMigrationKeys($shardId, array $keys)
    {
        $mkeys = [];
        foreach ($keys as $key) {
            if ($this->dispatchShard($key) !== $shardId) {
                $mkeys[] = $key;
            }
        }
        return $mkeys;
    }


}
