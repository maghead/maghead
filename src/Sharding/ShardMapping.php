<?php

namespace Maghead\Sharding;

use Exception;

class ShardMapping
{
    protected $mapping;

    const RANGE = 0;

    const HASH = 1;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * Return the type of this shard mapping.
     *
     * @return integer
     */
    public function getShardType()
    {
        if (isset($mapping['hash'])) {
            return self::HASH;
        } else if (isset($mapping['range'])) {
            return self::RANGE;
        }
    }

    /**
     * Return the ID of the related shards.
     *
     * @return string[]
     */
    public function getShardIds()
    {
        if (isset($this->mapping['hash'])) {
            return array_values($this->mapping['hash']);
        } else if (isset($this->mapping['range'])) {
            return array_keys($this->mapping['range']);
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
