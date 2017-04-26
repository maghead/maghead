<?php

namespace Maghead\Sharding;

use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ShardManager;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\Shard;
use Exception;

class ShardDispatcher
{
    protected $hasher;

    protected $shards;

    protected $mapping;

    public function __construct(ShardMapping $mapping, ShardCollection $shards)
    {
        $this->mapping = $mapping;
        $this->hasher = $mapping->getHasher();
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
     * Dispatch the key and return the related Chunk object
     *
     * @return Chunk
     */
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
     * This API is not stable, might be removed in the future.
     *
     * @unstable 
     *
     * @param string $shardId the shard ID
     * @param array $keys the keys in the chunk or shard.
     * @return array keys will need to migrate.
     */
    public function filterMigrationKeys($shardId, array $keys)
    {
        $mkeys = [];
        foreach ($keys as $key) {
            $index = $this->hasher->lookup($key);
            $chunk = $this->mapping->chunks[$index];
            if ($chunk['shard'] !== $shardId) {
                $mkeys[] = $key;
            }
        }
        return $mkeys;
    }


}
