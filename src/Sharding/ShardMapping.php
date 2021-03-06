<?php

namespace Maghead\Sharding;

use Exception;
use InvalidArgumentException;
use Maghead\Manager\DataSourceManager;
use Maghead\Sharding\Hasher\FastHasher;
use ArrayObject;

/**
 * shard mapping structure:
 *
 *    shards: [ s1, s2, 3 ]
 *    chunks: [
 *       chunkId => [ shard  => shardId ]
 *    ]
 */
class ShardMapping extends ArrayObject
{
    public $id;

    protected $chunkObjects = [];

    // Shard method
    const RANGE = 1;

    const HASH = 2;

    protected $dataSourceManager;

    /**
     * @var Maghead\Sharding\Hasher\Hasher
     */
    protected $hasher;

    public function __construct($id, array $conf, DataSourceManager $dataSourceManager)
    {
        $this->id       = $id;
        parent::__construct($conf, ArrayObject::ARRAY_AS_PROPS);
        $this->dataSourceManager = $dataSourceManager;

        // TODO: may be changed by config in future.
        $this->hasher = new FastHasher($this);
    }

    public function getKey()
    {
        return $this->key;
    }

    public function hasKeyGenerator()
    {
        return isset($this['key_generator']);
    }

    public function getKeyGenerator()
    {
        if (isset($this['key_generator'])) {
            return $this['key_generator'];
        }
    }

    public function getHasher()
    {
        return $this->hasher;
    }

    public function getDataSourceManager()
    {
        return $this->dataSourceManager;
    }

    /**
     * Return the type of this shard mapping.
     *
     * @return integer
     */
    public function getShardType()
    {
        if (isset($this['hash'])) {
            return self::HASH;
        } elseif (isset($this['range'])) {
            return self::RANGE;
        }
    }

    /**
     * Partition hash items into the parition array.
     *
     * @param int[] $hashes the hashed index.
     * @return array the array of partitions.
     */
    public function partition(array $hashes)
    {
        sort($hashes);
        $partitions = [];
        foreach ($this->chunks as $c) {
            $x = $c['index'];
            while (count($hashes) && $hashes[0] < $x) {
                $partitions[$x][] = array_shift($hashes);
            }
        }
        return $partitions;
    }

    /**
     * Replace the chunk with the new chunk objects.
     *
     * @param number $i the index of the chunk object. note, this index starts from zero.
     */
    public function replaceChunk($i, array $newchunks)
    {
        return array_splice($this->chunks, $i, 1, array_map(function($c) {
            if ($c instanceof Chunk) {
                return $c->toArray();
            }
            return $c;
        }, $newchunks));
    }

    public function sortChunks()
    {
        uasort($this->chunks, function($a, $b) {
            return $a['index'] <=> $b['index'];
        });
    }

    /**
     * Loads Chunk objects and return the map array.
     *
     * @return Chunk[chunk index]
     */
    public function loadChunks()
    {
        // make sure the chunks are in the correct order.
        $this->sortChunks();
        foreach ($this->chunks as $i => $c) {
            $this->chunkObjects[$i] = new Chunk($c['index'], $c['from'], $c['shard'], $this->dataSourceManager);
        }
        return $this->chunkObjects;
    }


    public function searchChunk($a)
    {
        if (is_numeric($a)) {
            $x = $a;
        } else if ($a instanceof Chunk) {
            $x = $a->index;
        } else {
            throw new \InvalidArgumentException("Invalid chunk argument");
        }

        foreach ($this->chunks as $i => $c) {
            if ($c['index'] === $x) {
                return $i;
            }
        }

        return false;
    }


    /**
     * Load the chunk object.
     *
     * @return Chunk
     */
    public function loadChunk($x)
    {
        foreach ($this->chunks as $c) {
            if ($c['index'] === $x) {
                return new Chunk($c['index'], $c['from'], $c['shard'], $this->dataSourceManager);
            }
        }
    }

    public function loadChunkByIndex($i)
    {
        $c = $this->chunks[$i];
        return new Chunk($c['index'], $c['from'], $c['shard'], $this->dataSourceManager);
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





    public function addShardId($shardId)
    {
        $this->shards[] = $shardId;
        array_unique($this->shards);
    }

    public function removeShardId($shardId)
    {
        if ($k = array_search($shardId, $this->shards)) {
            unset($this->shards[$k]);
        }
    }

    /**
     * Get shards used in this mapping.
     *
     * @return string[]
     */
    public function getShardIds()
    {
        if ($this->shards) {
            return $this->shards;
        }
        return $this->getUsingShardIds();
    }

    public function getHashBy()
    {
        return $this['hash'];
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
        foreach ($this->chunks as $c) {
            $shards[$chunk['shard']][] = $c['index'];
        }
        return array_keys($shards);
    }

    public function toArray()
    {
        return $this->getArrayCopy(); // this will copy the config array.
    }
}
