<?php

use Maghead\Sharding\Hasher\FastHasher;
use Maghead\Sharding\ShardMapping;

class FastHasherTest extends \PHPUnit\Framework\TestCase
{

    public function testLookup()
    {
        $mapping = new ShardMapping('mapping_store_id', 'store_id', ['s1', 's2', 's3'], [
            'c1' => 's1',
            'c2' => 's2',
            'c3' => 's3',
        ]);
        $hasher = new FastHasher($mapping);

        $testKey = crc32(30);
        $this->assertEquals(2473281379, $testKey);

        $n = $hasher->lookup(30);
        $this->assertEquals('c3', $n);

        $buckets = $hasher->getBuckets();
        $this->assertEquals([
            1591159457 => 'c1',
            2967030669 => 'c3',
            3353246491 => 'c2',
        ], $buckets);

        list($from, $to) = $hasher->lookupRange('c2.5');
        $this->assertEquals([
            'key' => 1591159457,
            'node' => 'c1',
        ] , $from);

        $this->assertEquals([
            'key' => 2967030669,
            'node' => 'c3',
        ] , $to);
    }
}
