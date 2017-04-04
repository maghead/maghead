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

    public function dispatch($key)
    {
        $chunkId = $this->hasher->hash($key);
        $chunk   = $this->mapping->chunks[$chunkId];
        $shardId = $chunk['shard'];
        return $this->shards[$shardId];
    }
}
