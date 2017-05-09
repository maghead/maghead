<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\ShardMapping;
use Maghead\Runtime\Config\Config;
use Maghead\Manager\ConfigManager as BaseConfigManager;

use MongoDB\Client;

class ConfigManager extends BaseConfigManager
{
    private $appId;

    private $client;

    private $collection;

    function __construct(Config $config)
    {
        parent::__construct($config);

        if ($url = $config->getConfigServerUrl()) {
            $this->appId = $config->getAppId();
            if (!$this->appId) {
                throw new \Exception("appId is required");
            }
            $this->client = new Client($url);
            $this->collection = $this->client->maghead->configs;
        }
    }

    public function addShardMapping(ShardMapping $mapping)
    {
        $this->config['sharding']['mappings'][$mapping->id] = $mapping->toArray();
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$set' => [ "sharding.mappings.{$mapping->id}" => $mapping->toArray() ]
            ], [ 'upsert' => true ]);
        }
    }

    public function removeShardMapping(ShardMapping $mapping)
    {
        unset($this->config['sharding']['mappings'][$mapping->id]);
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$unset' => [ "sharding.mappings.{$mapping->id}" => '' ]
            ], [ 'upsert' => true ]);
        }
    }

    public function removeShardMappingById($mappingId)
    {
        unset($this->config['sharding']['mappings'][$mappingId]);
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$unset' => [ "sharding.mappings.{$mappingId}" => '' ]
            ], [ 'upsert' => true ]);
        }
    }

    public function addDatabaseConfig($nodeId, array $node)
    {
        parent::addDatabaseConfig($nodeId, $node);
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$set' => [ "databases.{$nodeId}" => $node ]
            ], [ 'upsert' => true ]);
        }
    }

    public function removeDatabase($nodeId)
    {
        unset($this->config['databases'][$nodeId]);
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$unset' => [ "databases.{$nodeId}" => '' ]
            ], [ 'upsert' => true ]);
        }
    }

}
