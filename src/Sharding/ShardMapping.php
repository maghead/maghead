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

    public function __construct($id, array $conf)
    {
        $this->id       = $id;
        $this->key      = $conf['key'];
        $this->shardIds = $conf['shards'];
        $this->chunks   = $conf['chunks'];
        $this->extra    = $conf;
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
        if ($this->shardIds) {
            return $this->shardIds;
        }
        return $this->getUsingShardIds();
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
    public function getUsingShardIds()
    {
        $shards = [];
        foreach ($this->chunks as $chunkIndex => $chunk) {
            $shards[$chunk['shard']][] = $chunkIndex;
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
