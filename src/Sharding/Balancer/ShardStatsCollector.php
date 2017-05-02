<?php

namespace Maghead\Sharding\Balancer;

use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\ShardMapping;
use Maghead\Schema\BaseSchema;

class ShardStatsCollector
{
    protected $mapping;

    public function __construct(ShardMapping $mapping)
    {
        $this->mapping = $mapping;
    }

    public function collect(ShardCollection $shards, $schema)
    {
        $stats = [];
        $repoClass = $schema->getRepoClass();
        $hasher = $this->mapping->getHasher();

        foreach ($shards as $shard) {
            $repo = $shard->repo($repoClass);
            $startTime = microtime(true);

            $shardStats = [];
            $shardStats['numberOfRows'] = count($repo);
            $shardStats['keys'] = $repo->fetchShardKeyStats();
            foreach ($shardStats['keys'] as $keyStat) {
                $keyStat->hash = $hasher->hash($keyStat->shardKey);
            }

            // TODO: StatsCollector should be implemented in Shard Worker.
            // $shardStats['diskFreeSpace'] = disk_free_space('/');

            // TODO: query table/index stats
            $stats[$shard->id] = (object) $shardStats;

            $shard->setStats((object) $shardStats);
        }
        return $stats;
    }
}
