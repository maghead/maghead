<?php

namespace Maghead\Sharding\Hasher;

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
        // register chunk index directly
        foreach ($mapping->chunks as $i => $c) {
            $x = $c['index'];
            $this->buckets[$x] = $i;
            $this->targetIndexes[$i][] = $x;
        }
        ksort($this->buckets, SORT_REGULAR);
    }

    /**
     * Add a target into the buckets.
     *
     * @param string $target
     * @return integer hash index
     */
    public function addTarget($target)
    {
        $index = $this->hash($target);
        $this->buckets[$index] = $target;
        $this->targetIndexes[$target][] = $index;
        ksort($this->buckets, SORT_REGULAR);
        return $index;
    }

    /**
     * Add a target into the buckets using pre-computed index.
     *
     * @param integer $index
     * @param string $target
     */
    public function addIndexedTarget($index, $target)
    {
        $this->buckets[$index] = $target;
        $this->targetIndexes[$target][] = $index;
        ksort($this->buckets, SORT_REGULAR);
    }

    public function getBuckets()
    {
        return $this->buckets;
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
        foreach ($this->buckets as $x => $nodeId) {
            if ($x > $index) {
                return new HashRange($this, $target, $index, $from);
            }
            $from = $x;
        }
        return new HashRange($this, $target, $index, $from);
    }

    public function lookup($key)
    {
        $hash = $this->hash($key);
        foreach ($this->buckets as $x => $value) {
            if ($x > $hash) {
                return $value;
            }
        }
        reset($this->buckets);
        return current($this->buckets);
    }
}
