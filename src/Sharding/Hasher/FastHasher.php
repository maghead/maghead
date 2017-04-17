<?php

namespace Maghead\Sharding\Hasher;

use Flexihash\Flexihash;
use Maghead\Sharding\ShardMapping;

class FastHasher implements Hasher
{
    protected $mapping;

    protected $buckets = [];

    protected $targetIndexes = [];

    public function __construct(ShardMapping $mapping)
    {
        $this->mapping = $mapping;
        $this->addTargets(array_keys($mapping->chunks));
    }

    public function addTargets($targets, $numberOfReplica = 1)
    {
        foreach ($targets as $target) {
            for ($i = 0; $i < $numberOfReplica; $i++) {
                $index = $this->hash($target);
                $this->buckets[$index] = $target;
                $this->targetIndexes[$target][] = $index;
            }
        }
        ksort($this->buckets, SORT_REGULAR);
    }

    public function addTarget($target, $numberOfReplica = 1)
    {
        for ($i = 0; $i < $numberOfReplica; $i++) {
            $index = $this->hash($target);
            $this->buckets[$index] = $target;
            $this->targetIndexes[$target][] = $index;
        }
        ksort($this->buckets, SORT_REGULAR);
    }

    public function getBuckets()
    {
        return $this->buckets;
    }


    /**
     * Returns the indexes of the target
     *
     * @return number[]
     */
    public function indexesOf($target)
    {
        if (isset($this->targetIndexes[$target])) {
            return $this->targetIndexes[$target];
        }
        return false;
    }

    /**
     * Hash the key
     *
     * @return integer
     */
    public function hash($key)
    {
        return crc32($key);
    }

    /**
     * Return the range of the new target
     *
     * @return [from, to]
     */
    public function lookupRange($target)
    {
        // var_dump($this->buckets);
        ksort($this->buckets, SORT_REGULAR);
        $index = $this->hash($target);

        $lastNode = null;
        $lastKey = null;
        foreach ($this->buckets as $key => $nodeId) {
            if ($key > $index) {
                return [
                    ['key'=> $lastKey, 'node' => $lastNode],
                    ['key' => $key, 'node' => $nodeId],
                ];
            }
            $lastNode = $nodeId;
            $lastKey = $key;
        }
        return [['key'=> $lastKey, 'node' => $lastNode], null];
    }

    public function lookup($key)
    {
        $index = $this->hash($key);
        foreach ($this->buckets as $key => $value) {
            if ($key > $index) {
                return $value;
            }
        }
    }
}
