<?php

namespace Maghead\Sharding;

use Exception;
use InvalidArgumentException;
use Maghead\Manager\DataSourceManager;

/**
 * shard mapping structure:
 *
 *    shards: [ s1, s2, 3 ]
 *    chunks: [
 *       chunkId => [ shard  => shardId ]
 *    ]
 */
class ShardMapping
{
    public $id;

    public $key;

    public $shardIds;

    public $chunks;

    protected $config;

    // Shard method
    const RANGE = 1;

    const HASH = 2;

    protected $dataSourceManager;

    public function __construct($id, array $conf, DataSourceManager $dataSourceManager)
    {
        $this->id       = $id;
        $this->key      = $conf['key'];
        $this->shardIds = $conf['shards'];
        $this->chunks   = $conf['chunks'];
        $this->config    = $conf;
        $this->dataSourceManager = $dataSourceManager;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function hasKeyGenerator()
    {
        return isset($this->config['key_generator']);
    }

    public function getKeyGenerator()
    {
        if (isset($this->config['key_generator'])) {
            return $this->config['key_generator'];
        }
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
        } elseif (isset($this->config['range'])) {
            return self::RANGE;
        }
    }

    public function partition(array $hashes)
    {
        sort($hashes);

        $partitions = [];
        foreach ($this->chunks as $x => $c) {
            while (count($hashes) && $hashes[0] < $x) {
                $partitions[$x][] = array_shift($hashes);
            }
        }
        return $partitions;
    }

    /**
     * Insert a chunk into the chunk index.
     *
     * @param number $chunkIndex
     * @param string $shardId
     */
    public function insertChunk($chunkIndex, $shardId)
    {
        if ($chunkIndex > Chunk::HASH_RANGE) {
            throw new InvalidArgumentException("$chunkIndex should be less than {Chunk::HASH_RANGE}");
        }
        $this->chunks[$chunkIndex] = ['shard' => $shardId];
        ksort($this->chunks, SORT_REGULAR);
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
        return new Chunk($chunkIndex, $indexFrom, $config['shard'], $this->dataSourceManager);
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
        return $this->config['hash'];
    }

    public function loadShardCollection() : ShardCollection
    {
        $shardIds = $this->getShardIds();
        $shards = [];
        foreach ($shardIds as $shardId) {
            $shards[$shardId] = new Shard($shardId, $this->dataSourceManager);
        }
        return new ShardCollection($shards, $this);
    }

    public function loadShardCollectionOf($repoClass) : ShardCollection
    {
        $shardIds = $this->getShardIds();
        $shards = [];
        foreach ($shardIds as $shardId) {
            $shards[$shardId] = new Shard($shardId, $this->dataSourceManager);
        }
        return new ShardCollection($shards, $this, $repoClass);
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
        $conf = $this->config; // this will copy the config array.
        $conf['key'] = $this->key;
        $conf['shards'] = $this->shardIds;
        $conf['chunks'] = $this->chunks;
        return $conf;
    }
}
