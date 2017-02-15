<?php

namespace Maghead\Sharding\QueryMapper\Gearman;

/**
 * GearmanShardQueryJob is used when:
 *
 * 1. On client side, sending job to worker.
 * 2. On worker side, receiving job from client.
 */
class GearmanQueryJob
{
    public $shardId;

    public $query;

    public function __construct(string $shardId, $query)
    {
        $this->shardId = $shardId;
        $this->query = $query;
    }
}
