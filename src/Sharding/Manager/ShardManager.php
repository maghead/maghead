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
     * config of ".sharding"
     */
    protected $config;

    /**
     * @var DataSourceManager this is used for selecting read/write nodes.
     */
    protected $dataSourceManager;

    public function __construct(Config $config, DataSourceManager $dataSourceManager = null)
    {
        $this->config = $config;
        $this->dataSourceManager = $dataSourceManager
            ? $dataSourceManager
            : new DataSourceManager($config->getDataSources());
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
        return isset($this->config['sharding']['mappings']);
    }


    public function addShardMapping(ShardMapping $mapping)
    {
        $this->config['sharding']['mappings'][$mapping->id] = $mapping->toArray();
    }

    public function loadShardMapping(string $mappingId) : ShardMapping
    {
        if (!isset($this->config['sharding']['mappings'][$mappingId])) {
            throw new LogicException("MappingId '$mappingId' is undefined.");
        }

        return new ShardMapping($mappingId, $this->config['sharding']['mappings'][$mappingId], $this->dataSourceManager);
    }

    public function loadShard($shardId) : Shard
    {
        return new Shard($shardId, $this->dataSourceManager);
    }

    public function loadShardCollectionOf($mappingId, $repoClass = null) : ShardCollection
    {
        $mapping = $this->loadShardMapping($mappingId);
        return $mapping->loadShardCollectionOf($repoClass);
    }

    public function getConfig()
    {
        return $this->config;
    }
}
