<?php

namespace Maghead\Sharding;

use Maghead\Sharding\Shard;
use Maghead\Schema\BaseSchema;

class ChunkStatsCollector
{
    protected $mapping;

    public function __construct(ShardMapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function collect(BaseSchema $schema)
    {
        $stats = [];
        $repoClass = $schema->getRepoClass();
        foreach ($this->shards as $shard) {
            $repo = $shard->repo($repoClass);
            /*
            $startTime = microtime(true);
            $stats[$shard->id]['rows'] = count($repo);
            $stats[$shard->id]['queryTime'] = microtime(true) - $startTime;
             */
            // TODO: query table/index stats
        }
        return $stats;
    }
}
