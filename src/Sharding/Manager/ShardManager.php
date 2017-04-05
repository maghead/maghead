<?php
namespace Maghead\Sharding\Manager;

use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ConnectionManager;
use Maghead\Config;

use LogicException;
use Exception;
use ArrayIterator;
use Iterator;
use IteratorAggregate;

class ShardManager
{
    protected $config;

    /**
     * config of ".sharding"
     */
    protected $shardingConfig;

    /**
     * @var DataSourceManager this is used for selecting read/write nodes.
     */
    protected $dataSourceManager;

    public function __construct(Config $config, DataSourceManager $dataSourceManager)
    {
        $this->config = $config;
        $this->shardingConfig = $config['sharding'];
        $this->dataSourceManager = $dataSourceManager;
    }

    public function getMappingsConfig()
    {
        if (isset($this->shardingConfig['mappings'])) {
            return $this->shardingConfig['mappings'];
        }
    }

    public function hasShardMapping(string $mappingId)
    {
        return isset($this->shardingConfig['mappings']);
    }


    public function getShardMapping(string $mappingId) : ShardMapping
    {
        if (!isset($this->shardingConfig['mappings'][$mappingId])) {
            throw new LogicException("MappingId '$mappingId' is undefined.");
        }

        $conf = $this->shardingConfig['mappings'][$mappingId];
        return new ShardMapping($mappingId, $conf['key'], $conf['shards'], $conf['chunks'], $conf);
    }


    public function getShard($shardId)
    {
        return new Shard($shardId, $this->dataSourceManager);
    }

    public function getShardsOf($mappingId, $repoClass = null)
    {
        $mapping = $this->getShardMapping($mappingId);
        $shardIds = $mapping->selectShards();
        $shards = [];
        foreach ($shardIds as $shardId) {
            $shards[$shardId] = new Shard($shardId, $this->dataSourceManager);
        }
        return new ShardCollection($shards, $mapping, $repoClass);
    }

    public function createShardDispatcherOf(string $mappingId)
    {
        $mapping = $this->getShardMapping($mappingId);
        $shards = $this->getShardsOf($mappingId);
        return new ShardDispatcher($mapping, new FlexihashHasher($mapping), $shards);
    }
}
