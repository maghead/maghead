<?php

namespace Maghead\Sharding;

use Exception;

/**
 * config structure:
 *
 *    chunks: [
 *       chunkId => [ shard  => shardId, dbname => dbname ]
 *    ]
 *    shards: string[]
 */
class ShardMapping
{
    public $id;

    public $key;

    public $shardIds;

    public $chunks;

    public $extra;

    // Shard method
    const RANGE = 1;

    const HASH = 2;

    public function __construct($id, $key, array $shardIds, array $chunks, array $extra = [])
    {
        $this->id       = $id;
        $this->key      = $key;
        $this->shardIds = $shardIds;
        $this->chunks   = $chunks;
        $this->extra    = $extra;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function hasKeyGenerator()
    {
        return isset($this->extra['key_generator']);
    }

    public function getKeyGenerator()
    {
        if (isset($this->extra['key_generator'])) {
            return $this->extra['key_generator'];
        }
    }


    /**
     * Return the type of this shard mapping.
     *
     * @return integer
     */
    public function getShardType()
    {
        if (isset($this->extra['hash'])) {
            return self::HASH;
        } elseif (isset($this->extra['range'])) {
            return self::RANGE;
        }
    }

    public function getChunk($chunkId)
    {
        return $this->chunks[$chunkId];
    }

    /**
     * Get the defined chunks in this mapping.
     */
    public function getChunks()
    {
        return $this->chunks;
    }

    public function setChunks(array $chunks)
    {
        $this->chunks = $chunks;
    }

    /**
     * Get shards used in this mapping.
     *
     * @return string[]
     */
    public function getShardIds()
    {
        return $this->shardIds;
    }


    public function getHashBy()
    {
        return $this->extra['hash'];
    }

    public function getRangeBy()
    {
        return $this->extra['range'];
    }


    /**
     * Select shards by the given shard collection.
     *
     * @return Shard[string shardId]
     */
    public function selectShards(array $availableShards)
    {
        // TODO: check shard method and use different selection method here
        $shards = [];
        foreach ($this->chunks as $chunkId => $chunk) {
            $shardId = $chunk['shard'];

            // Use shardId instead of chunkId
            if (!isset($availableShards[$shardId])) {
                throw new Exception("Shard '$shardId' is not defined in available shards.");
            }
            $shards[$shardId] = $availableShards[$shardId];
        }
        return $shards;
    }

    public function toArray()
    {
        $conf = $this->extra;
        $conf['key'] = $this->key;
        $conf['shards'] = $this->shardIds;
        $conf['chunks'] = $this->chunks;
        return $conf;
    }
}
