<?php

namespace Maghead\Sharding\Balancer\Policy;

use Maghead\Sharding\Chunk;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Sharding\ShardMapping;

use Maghead\Sharding\Balancer\MigrateInfo;

use Maghead\Schema\BaseSchema;


class ConservativeShardBalancerPolicy implements ShardBalancerPolicy
{
    const MILLION = 1000000;

    // Migrate one chunk at one time.
    public $maxNumberOfMigrationChunks = 1;

    /**
     * the max number of rows per shard,
     * this will trigger the chunk migration.
     */
    public $maxNumberOfRowsPerShard = 1 * self::MILLION; // 1M

    public $maxUsageRatio = 2.0;

    public function __construct($maxNumberOfMigrationChunks = 1, $maxNumberOfRowsPerShard = 1 * self::MILLION, $maxUsageRatio = 2)
    {
        $this->maxNumberOfMigrationChunks = $maxNumberOfMigrationChunks;
        $this->maxNumberOfRowsPerShard = $maxNumberOfRowsPerShard;
        $this->maxUsageRatio = $maxUsageRatio;
    }

    /**
     * Returns a set of Chunks to be moved, MigrationInfo
     */
    public function balance(ShardCollection $shards, array $chunks)
    {
        $shardChunks = [];
        foreach ($chunks as $i => $c) {
            $shardChunks[$c->shardId][$i] = $c;
        }

        $averageNumberOfRows = $this->calculateAvgRows($shards);
        $from = $this->getMostOverloadedShard($shards, $averageNumberOfRows);
        if (!$from) {
            return [];
        }

        $to = $this->getLeastLoadedShard($shards);

        // Used for finding jumbo chunks
        $distribution = $this->getKeyspaceDistribution($from);

        // Get chunks of the shard
        $chunks = $shardChunks[$from->id];
        ksort($chunks, SORT_REGULAR);

        // Aggregate the hash/key to the chunks
        $chunkDistribution = [];
        $c = current($chunks);
        $x = key($chunks);
        foreach ($distribution as $hash => $stat) {
            // find the chunk that contains this hash
            while (!$c->contains($hash)) {
                $c = next($chunks);
                $x = key($chunks);
            }
            if (!isset($chunkDistribution[$x]['rows'])) {
                $chunkDistribution[$x]['rows'] = 0;
            }
            $chunkDistribution[$x]['rows']  += $stat->rows;
            $chunkDistribution[$x]['keys'][] = $stat->key;
            $chunkDistribution[$x]['hashes'][] = $hash;
        }

        uasort($chunkDistribution, function($a, $b) {
            return $a['rows'] <=> $b['rows'];
        });

        return array_map(function($index) use ($to, $chunks, $chunkDistribution) {
            return new MigrateInfo($to, $chunks[$index], $chunkDistribution[$index]['keys']);
        }, array_slice(array_keys($chunkDistribution), 0, $this->maxNumberOfMigrationChunks));
    }

    protected function calculateAvgRows(ShardCollection $shards)
    {
        $averageNumberOfRows = 0;
        foreach ($shards as $shardId => $shard) {
            $stat = $shard->getStats();
            /*
            var_dump($stat->queryTime);
            var_dump($stat->keys);
            var_dump($stat->numberOfRows);
            */
            $averageNumberOfRows += $stat->numberOfRows;
        }
        return $averageNumberOfRows / count($shards);
    }

    /**
     * Returns the most overloaded shard
     *
     * Returns false when the shard is not found.
     */
    protected function getMostOverloadedShard(ShardCollection $shards, $averageNumberOfRows)
    {
        // Find shards has extreme higher number of rows between the average rows.
        $best = false;
        $minRatio = 1.0;
        foreach ($shards as $shardId => $shard) {
            $shardStat = $shard->getStats();

            if ($this->maxNumberOfRowsPerShard !== false && $shardStat->numberOfRows < $this->maxNumberOfRowsPerShard) {
                continue;
            }

            $r = $shardStat->numberOfRows / $averageNumberOfRows;
            if ($r > $this->maxUsageRatio && $r > $minRatio) {
                $minRatio = $r;
                $best = $shard;
            }
        }
        return $best;
    }

    public function isShardSuitable(Shard $shard)
    {
        return true;
    }

    protected function getLeastLoadedShard(ShardCollection $shards)
    {
        // Find shards has extreme higher number of rows between the average rows.
        $best = false;
        $max = PHP_INT_MAX;
        foreach ($shards as $shardId => $shard) {

            if (!$this->isShardSuitable($shard)) {
                continue;
            }

            $shardStat = $shard->getStats();

            if ($shardStat->numberOfRows > $max) {
                continue;
            }

            $max = $shardStat->numberOfRows;
            $best = $shard;
        }
        return $best;
    }

    /**
     * Calcualte the keyspace distribution from the given shard.
     */
    protected function getKeyspaceDistribution(Shard $shard)
    {
        $distribution = [ ];
        $stats = $shard->getStats();
        foreach ($stats->keys as $keyStat) {
            $distribution[$keyStat->hash] = (object) [
                'key'   => $keyStat->shardKey,
                'rows'  => $keyStat->numberOfRows,
                // 'shard' => $shard->id,
            ];
        }
        // This costs O(log N), where N could be a number of million when shard
        // key is an unique identity.
        ksort($distribution, SORT_REGULAR);
        return $distribution;
    }
}
