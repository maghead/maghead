<?php
namespace Maghead\Sharding\Manager;

use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\Hasher\FastHasher;
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
    /**
     * @var Maghead\Config
     */
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


    /**
     * @codeCoverageIgnore
     */
    public function getDataSourceManager()
    {
        return $this->dataSourceManager;
    }

    public function hasShardMapping(string $mappingId)
    {
        return isset($this->shardingConfig['mappings']);
    }

    public function loadShardMapping(string $mappingId) : ShardMapping
    {
        if (!isset($this->shardingConfig['mappings'][$mappingId])) {
            throw new LogicException("MappingId '$mappingId' is undefined.");
        }

        return new ShardMapping($mappingId, $this->shardingConfig['mappings'][$mappingId], $this->dataSourceManager);
    }

    public function laodShard($shardId) : Shard
    {
        return new Shard($shardId, $this->dataSourceManager);
    }

    public function loadShardCollectionOf($mappingId, $repoClass = null) : ShardCollection
    {
        $mapping = $this->loadShardMapping($mappingId);
        $shardIds = $mapping->getShardIds();
        $shards = [];
        foreach ($shardIds as $shardId) {
            $shards[$shardId] = new Shard($shardId, $this->dataSourceManager);
        }
        return new ShardCollection($shards, $mapping, $repoClass);
    }
}
