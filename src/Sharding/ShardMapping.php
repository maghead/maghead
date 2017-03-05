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

    protected $extra;

    // Shard method
    const RANGE = 1;

    const HASH = 2;

    public function __construct($id, $key, array $shardIds, array $chunks, array $targets, array $extra = [])
    {
        $this->id = $id;
        $this->key = $key;
        $this->shardIds = $shardIds;
        $this->chunks = $chunks;
        $this->targets = $targets;
        $this->extra = $extra;
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
        } else if (isset($this->extra['range'])) {
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
     * Return the ID of the related shards.
     *
     * @return string[]
     */
    public function getTargetIds()
    {
        if (isset($this->extra['hash'])) {
            return $this->extra['hash'];
        } else if (isset($this->extra['range'])) {
            return array_keys($this->extra['range']);
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

    public function toArray()
    {
        $conf = $this->extra;
        $conf['key'] = $this->key;
        $conf['shards'] = $this->shardIds;
        $conf['chunks'] = $this->chunks;
        $conf['targets'] = $this->targets;
        return $conf;
    }

}
