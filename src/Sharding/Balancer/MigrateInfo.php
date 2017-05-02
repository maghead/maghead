<?php

namespace Maghead\Sharding\Balancer;

use Maghead\Sharding\Chunk;
use Maghead\Sharding\Shard;

class MigrateInfo {

    public $chunk;

    public $keys;

    /**
     * @var Shard migrate target
     */
    public $to;

    public function __construct(Shard $shard, Chunk $chunk, array $keys) {
        $this->to    = $shard;
        $this->chunk = $chunk;
        $this->keys  = $keys;
    }

    public function __debugInfo()
    {
        return [
            'toShard' => $this->to->id,
            'chunk' => $this->chunk->__debugInfo(),
            'keys' => $this->keys,
        ];
    }
}
