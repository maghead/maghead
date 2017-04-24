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

    /**
     * load the chunk object.
     */
    public function loadChunk($chunkIndex)
    {
        $indexFrom = 0;
        foreach ($this->chunks as $i => $c) {
            if ($i === $chunkIndex) {
                break;
            }
            $indexFrom = $i;
        }
        $config = $this->chunks[$chunkIndex]; // get the chunk config.
        return new Chunk($chunkIndex, $indexFrom, $config);
    }

    public function getChunkConfig($chunkIndex)
    {
        if (isset($this->chunks[$chunkIndex])) {
            return $this->chunks[$chunkIndex];
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
     * Select shards by the given shard collection.
     *
     * @return Shard[string shardId]
     */
    public function selectShards()
    {
        $shards = [];
        foreach ($this->chunks as $chunkId => $chunk) {
            $shardId = $chunk['shard'];
            $shards[$shardId] = true;
        }
        return array_keys($shards);
    }

    public function toArray()
    {
        $conf = $this->extra; // this will copy the extra array.
        $conf['key'] = $this->key;
        $conf['shards'] = $this->shardIds;
        $conf['chunks'] = $this->chunks;
        return $conf;
    }
}
