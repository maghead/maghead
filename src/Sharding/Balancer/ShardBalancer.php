<?php

namespace Maghead\Sharding\Balancer;

use Maghead\Schema\BaseSchema;

use Maghead\Sharding\ShardMapping;

use Maghead\Sharding\Balancer\Policy\ConservativeShardBalancerPolicy;
use Maghead\Sharding\Balancer\Policy\ShardBalancerPolicy;

use CLIFramework\Logger;

class ShardBalancer
{
    protected $schemas;

    protected $logger;

    /**
     * @param BaseSchema[] $schemas schemas to be balanced.
     */
    public function __construct(ShardBalancerPolicy $policy, Logger $logger = null)
    {
        $this->policy = $policy;
        $this->logger = $logger;
    }

    /**
     * Check if the allocation of chunks in the shard mapping is balanced.
     *
     * @return bool
     */
    public function balance(ShardMapping $mapping, array $schemas)
    {
        $shards = $mapping->loadShardCollection();
        $chunks = $mapping->loadChunks();

        $schema = $schemas[0];
        $collector = new ShardStatsCollector($mapping);

        // This prepares shard stats
        $collector->collect($shards, $schema);
        $migrateInfo = $this->policy->balance($shards, $chunks);

        // TODO: use Migration manager to migrate chunks
        var_dump($migrateInfo);

        foreach ($migrateInfo as $mInfo) {

        }
    }

    // Handle the migration here
}
