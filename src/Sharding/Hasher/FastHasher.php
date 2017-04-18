<?php

namespace Maghead\Sharding\Hasher;

use Flexihash\Flexihash;
use Maghead\Sharding\ShardMapping;

class HashRange
{
    public $hasher;

    /**
     * @var string $target the new target.
     */
    public $target;

    public $index;

    public $from;

    public function __construct(Hasher $hasher, $target, $index, $from)
    {
        $this->hasher = $hasher;
        $this->target = $target;
        $this->index = $index;
        $this->from = $from;
    }

    public function in($key)
    {
        $index = $this->hasher->hash($key);
        if ($index <= $this->from) {
            return false;
        }
        if ($index > $this->index) {
            return false;
        }
        return true;
    }
}

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

    public function addTargets($targets)
    {
        foreach ($targets as $target) {
            $index = $this->hash($target);
            $this->buckets[$index] = $target;
            $this->targetIndexes[$target][] = $index;
        }
        ksort($this->buckets, SORT_REGULAR);
    }

    public function addTarget($target)
    {
        $index = $this->hash($target);
        $this->buckets[$index] = $target;
        $this->targetIndexes[$target][] = $index;
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
    public function keysOf($target)
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
        ksort($this->buckets, SORT_REGULAR);
        $index = $this->hash($target);

        $from = 0;
        foreach ($this->buckets as $key => $nodeId) {
            if ($key > $index) {
                return new HashRange($this, $target, $index, $from);
            }
            $from = $key;
        }
        return new HashRange($this, $target, $index, $from);
    }

    public function lookup($key)
    {
        $index = $this->hash($key);
        foreach ($this->buckets as $key => $value) {
            if ($key > $index) {
                return $value;
            }
        }
        reset($this->buckets);
        $first = current($this->buckets);
        return $first;
    }
}
