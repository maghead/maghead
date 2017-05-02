<?php

namespace Maghead\Sharding\Balancer;

use Maghead\Schema\BaseSchema;

use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Manager\ChunkManager;
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
        $collector->collect($shards, $schema);

        $migrateInfo = $this->policy->balance($shards, $chunks);
        $chunkManager = new ChunkManager($mapping);
        foreach ($migrateInfo as $mInfo) {
            $rets = $chunkManager->move($mInfo->chunk, $mInfo->to, $schemas);

            $failed = false;
            foreach ($rets as $tables => $ret) {
                if ($ret->error) {
                    $failed = true;
                    break;
                }
            }
            if ($failed) {
                $mInfo->setFailed();
            } else {
                $mInfo->setSucceed();
            }
        }
        return $migrateInfo;
    }
}
