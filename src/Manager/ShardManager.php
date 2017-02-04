<?php

namespace Maghead\Manager;

use Maghead\Sharding\Hasher\FlexihashHasher;
use Maghead\Sharding\ShardDispatcher;
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
        var_dump( $this->shardingConfig );
        $this->connectionManager = $connectionManager;
    }

    public function getMappings()
    {
        if (isset($this->shardingConfig['mappings'])) {
            return $this->shardingConfig['mappings'];
        }
    }

    public function getMapping($mappingId)
    {
        if (!isset($this->shardingConfig['mappings'][$mappingId])) {
            throw new LogicException("MappingId $mappingId is undefined.");
        }
        return $this->shardingConfig['mappings'][$mappingId];
    }

    public function getGroupDefinitions()
    {
        return $this->shardingConfig['groups'];
    }

    public function createShardDispatcher(string $mappingId, string $repoClass)
    {
        $mapping = $this->getMapping($mappingId);
        if (!isset($mapping['hash'])) {
            throw new Exception("sharding method is not supported.");
        }
        $hasher = new FlexihashHasher($mapping);
        $dispatcher = new ShardDispatcher($this->connectionManager, $hasher, $this->shardingConfig['groups'], $repoClass);
        return $dispatcher;
    }
}
