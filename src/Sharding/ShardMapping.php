<?php

namespace Maghead\Sharding;

use Exception;

class ShardMapping
{
    protected $config;

    const RANGE = 0;

    const HASH = 1;

    public function __construct(array $mapping)
    {
        $this->config = $mapping;
    }

    /**
     * Return the type of this shard mapping.
     *
     * @return integer
     */
    public function getShardType()
    {
        if (isset($this->config['hash'])) {
            return self::HASH;
        } else if (isset($this->config['range'])) {
            return self::RANGE;
        }
    }


    public function getHash()
    {
        return $this->config['hash'];
    }

    public function getRange()
    {
        return $this->config['range'];
    }


    /**
     * Return the ID of the related shards.
     *
     * @return string[]
     */
    public function getShardIds()
    {
        if (isset($this->config['hash'])) {
            return array_values($this->config['hash']);
        } else if (isset($this->config['range'])) {
            return array_keys($this->config['range']);
        }
        throw new Exception('hash / range is undefined.');
    }

    /**
     * Select shards by keys
     *
     * @return Shard[string shardId]
     */
    public function selectShards(array $availableShards)
    {
        $ids = $this->getShardIds();
        $shards = [];
        foreach ($ids as $id) {
            if (!isset($availableShards[$id])) {
                throw new Exception("Shard '$id' is not defined in available shards.");
            }
            $shards[$id] = $availableShards[$id];
        }
        return $shards;
    }
}
