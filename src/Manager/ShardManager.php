<?php

namespace Maghead\Manager;

use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
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

    protected $shardingConfig;

    protected $connectionManager;

    public function __construct(Config $config, ConnectionManager $connectionManager)
    {
        $this->config = $config;
        $this->shardingConfig = $config['sharding'];
        $this->connectionManager = $connectionManager;

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
        return new ShardMapping($this->shardingConfig['mappings'][$mappingId]);
    }


    public function getShard($shardId)
    {
        $shards = $this->shardingConfig['shards'];
        if (!isset($shards[$shardId])) {
            throw new Exception("Shard '{$shardId}' is undefined.");
        }
        return new Shard($shardId, $shards[$shardId], $this->connectionManager);
    }

    public function getShardsOf(string $mappingId)
    {
        $mapping = $this->getShardMapping($mappingId);
        $config = $mapping->selectShards($this->shardingConfig['shards']);
        $shards = [];
        foreach ($config as $shardId => $shardConfig) {
            // Wrap shard config into objects.
            $shard = new Shard($shardId, $shardConfig, $this->connectionManager);
            $shards[ $shardId ] = $shard;
        }
        return $shards;
    }

    public function createShardDispatcherOf(string $mappingId)
    {
        $mapping = $this->getShardMapping($mappingId);
        $shards = $this->getShardsOf($mappingId);
        $hasher = new FlexihashHasher($mapping);
        return new ShardDispatcher($hasher, $shards);
    }
}
