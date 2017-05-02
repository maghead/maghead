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

    public $status = null;

    const SUCCEED = TRUE;

    const FAILED = FALSE;

    public function __construct(Shard $shard, Chunk $chunk, array $keys) {
        $this->to    = $shard;
        $this->chunk = $chunk;
        $this->keys  = $keys;
    }

    public function setFailed()
    {
        $this->status = self::FAILED;
    }

    public function setSucceed()
    {
        $this->status = self::SUCCEED;
    }

    public function succeed()
    {
        return $this->status === self::SUCCEED;
    }

    public function failed()
    {
        return $this->status === self::FAILED;
    }

    public function __debugInfo()
    {
        return [
            'toShard' => $this->to,
            'chunk' => $this->chunk,
            'keys' => $this->keys,
        ];
    }
}
