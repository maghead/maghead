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
    protected $id;

    protected $key;

    protected $shardIds;

    protected $chunks;

    protected $targets;

    protected $config;

    const RANGE = 0;

    const HASH = 1;

    public function __construct($id, $key, array $shardIds, array $chunks, array $targets, array $config)
    {
        $this->id = $id;
        $this->key = $key;
        $this->shardIds = $shardIds;
        $this->chunks = $chunks;
        $this->targets = $targets;
        $this->config = $config;
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

    /**
     * Get the defined chunks in this mapping.
     */
    public function getChunks()
    {
        return $this->chunks;
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
        return $this->config['hash'];
    }

    public function getRangeBy()
    {
        return $this->config['range'];
    }


    /**
     * Return the ID of the related shards.
     *
     * @return string[]
     */
    public function getTargetIds()
    {
        if (isset($this->config['hash'])) {
            return $this->config['hash'];
        } else if (isset($this->config['range'])) {
            return array_keys($this->config['range']);
        }
        throw new Exception('hash / range is undefined.');
    }

    public function resolveChunk($chunkId)
    {
        if (!isset($this->chunks[$chunkId])) {
            throw new Exception("Chunk {$chunkId} is not defined.");
        }
        return $this->chunks[$chunkId];
    }

    /**
     * Select shards by the given shard collection.
     *
     * @return Shard[string shardId]
     */
    public function selectShards(array $availableShards)
    {
        // TODO: check shard method and use different selection method here
        $targetIds = $this->getTargetIds();
        $shards = [];
        foreach ($targetIds as $targetId => $chunkId) {
            $chunk = $this->resolveChunk($chunkId);
            $shardId = $chunk['shard'];

            // Use shardId instead of chunkId
            if (!isset($availableShards[$shardId])) {
                throw new Exception("Shard '$shardId' is not defined in available shards.");
            }
            $shards[$shardId] = $availableShards[$shardId];
        }
        return $shards;
    }
}
