<?php

namespace Maghead\Sharding;

use Maghead\Manager\ConnectionManager;
use Maghead\Manager\ShardManager;
use Maghead\Sharding\Hasher\Hasher;
use Exception;

class ShardDispatcher
{
    protected $hasher;

    protected $shards;

    public function __construct(Hasher $hasher, array $shards)
    {
        $this->hasher = $hasher;
        $this->shards = $shards;
    }

    public function dispatch($key)
    {
        $shardId = $this->hasher->hash($key);
        return $this->shards[$shardId];
    }
}
