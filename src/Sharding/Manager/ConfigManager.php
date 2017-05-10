<?php

namespace Maghead\Sharding\Manager;

use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Chunk;
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


    /**
     * Update only one chunk from the shard mapping
     */
    public function updateShardMappingChunk(ShardMapping $mapping, Chunk $chunk)
    {
        $i = $mapping->searchChunk($chunk);
        if ($i === false) {
            throw new \InvalidArgumentException("Chunk {$chunk->index} not found.");
        }

        $this->config['sharding']['mappings'][$mapping->id]['chunks'][$i] = $chunk->toArray();
        if ($this->client) {
            return $this->collection->updateOne([
                "appId" => $this->appId,

                // locate the correct chunk index in '$'
                "sharding.mappings.{$mapping->id}.chunks.index" => $chunk->index,
            ], [
                '$set' => [ "sharding.mappings.{$mapping->id}.chunks.$" => $chunk->toArray() ]
            ], [ 'upsert' => true ]);
        }
    }


    /**
     * update the chunks of one shard mapping
     */
    public function updateShardMappingChunks(ShardMapping $mapping)
    {
        $this->config['sharding']['mappings'][$mapping->id] = $mapping->toArray();
        if ($this->client) {
            return $this->collection->updateOne([
                'appId' => $this->appId,
            ], [
                '$set' => [ "sharding.mappings.{$mapping->id}.chunks" => $mapping->getChunks() ]
            ], [ 'upsert' => true ]);
        }
    }

    /**
     * set (update) shard mapping config.
     *
     * If the config server url is defined, the whole shard mapping will be
     * updated to the config server.
     */
    public function setShardMapping(ShardMapping $mapping)
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

    public function removeInstance($nodeId)
    {
        unset($this->config['instances'][$nodeId]);
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$unset' => [ "instances.{$nodeId}" => '' ]
            ], [ 'upsert' => true ]);
        }
    }

    public function addInstance($nodeId, $dsnArg, array $opts = null)
    {
        $node = parent::addInstance($nodeId, $dsnArg, $opts);
        if ($this->client) {
            return $this->collection->updateOne([ 'appId' => $this->appId ], [
                '$set' => [ "instances.{$nodeId}" => $node ]
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
