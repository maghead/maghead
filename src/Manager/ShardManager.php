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

    protected function getMappingGroups(array $mapping)
    {
        $gIds = [];
        if (isset($mapping['hash'])) {
            $gIds = array_values($mapping['hash']);
        } else if (isset($mapping['range'])) {
            $gIds = array_keys($mapping['range']);
        }
        $groups = [];
        foreach ($gIds as $gId) {
            $groups[$gId] = $this->shardingConfig['groups'][$gId];
        }
        return $groups;
    }



    public function getMappingReadNodes($mappingId)
    {
        $groups = $this->getMappingGroups($mappingId);
        $conns = [];
        foreach ($groups as $gId => $group) {
            $conns[$gId] = $this->connectionManager->getConnection(array_rand($group['read']));
        }
        return $conns;
    }

    public function getShardGroups()
    {
        $groups = [];
        foreach ($gIds as $gId) {
            $group = $this->shardingConfig['groups'][$gId];
        }
        return $groups;
    }

    public function getMappingWriteNodes($mappingId)
    {
        $groups = $this->getMappingGroups($mappingId);
        $conns = [];
        foreach ($groups as $gId => $group) {
            $conns[$gId] = $this->connectionManager->getConnection(array_rand($group['write']));
        }
        return $conns;
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
