<?php

namespace Maghead\Sharding\Balancer;

use Maghead\Sharding\Shard;

class ShardStatistics
{
    public $schemaStats = [];

    protected $shard;

    function __construct(Shard $shard, array $schemaStats)
    {
        $this->shard = $shard;
        $this->schemaStats = $schemaStats;
    }

    /*
    $shardStats['numberOfRows'] = count($repo);
    $shardStats['queryTime'] = microtime(true) - $startTime;
    $shardStats['keys'] = $repo->fetchShardKeyStats();
    */
}



