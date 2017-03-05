<?php

namespace Maghead\Sharding;

use Maghead\Manager\ConnectionManager;
use Maghead\Manager\ShardManager;
use Maghead\Sharding\Hasher\Hasher;
use Maghead\Sharding\ShardMapping;
use Exception;

class ShardDispatcher
{
    protected $hasher;

    protected $shards;

    protected $mapping;

    public function __construct(ShardMapping $mapping, Hasher $hasher, array $shards)
    {
        $this->mapping = $mapping;
        $this->hasher = $hasher;
        $this->shards = $shards;
    }

    public function dispatch($key)
    {
        $chunkId = $this->hasher->hash($key);
        $chunk = $this->mapping->resolveChunk($chunkId);
        $shardId = $chunk['shard'];
        return $this->shards[$shardId];
    }
}
