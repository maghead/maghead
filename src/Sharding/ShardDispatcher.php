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

    protected $repoClass;

    public function __construct(Hasher $hasher, array $shards, string $repoClass)
    {
        $this->hasher = $hasher;
        $this->shards = $shards;
        $this->repoClass = $repoClass;
    }

    public function dispatch($key)
    {
        $shardId = $this->hasher->hash($key);
        return $this->shards[$shardId];
    }
}
