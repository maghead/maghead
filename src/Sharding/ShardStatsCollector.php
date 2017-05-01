<?php

namespace Maghead\Sharding;

use Maghead\Sharding\Shard;
use Maghead\Schema\BaseSchema;

class ShardStatsCollector
{
    protected $shards;

    public function __construct(ShardCollection $shards)
    {
        $this->shards = $shards;
    }

    public function collect(BaseSchema $schema)
    {
        $stats = [];
        $repoClass = $schema->getRepoClass();
        foreach ($this->shards as $shard) {
            $repo = $shard->repo($repoClass);
            $startTime = microtime(true);
            $stats[$shard->id]['rows'] = count($repo);
            $stats[$shard->id]['queryTime'] = microtime(true) - $startTime;
            // TODO: query table/index stats
        }
        return $stats;
    }
}
