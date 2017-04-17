<?php

use Maghead\Sharding\Hasher\FastHasher;
use Maghead\Sharding\ShardMapping;

class FastHasherTest extends \PHPUnit\Framework\TestCase
{
    protected $mapping;

    public function setUp()
    {
        $this->mapping = new ShardMapping('mapping_store_id', 'store_id', ['s1', 's2', 's3'], [
            'c1' => 's1',
            'c2' => 's2',
            'c3' => 's3',
        ]);
    }


    public function testLookup()
    {
        $hasher = new FastHasher($this->mapping);

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
    }

    /**
     * @depends testLookup
     */
    public function testLookupRange()
    {
        $hasher = new FastHasher($this->mapping);
        $range = $hasher->lookupRange('c2.5');
        $this->assertEquals([
            'key' => 1591159457,
            'node' => 'c1',
        ] , $range->from);

        $this->assertEquals([
            'key' => 2967030669,
            'node' => 'c3',
        ] , $range->to);

        return $range;
    }


    /**
     * @depends testLookupRange
     */
    public function testRangeIn($range)
    {
        $this->assertTrue($range->in('c2.5'));

        // should not include 'from'
        $this->assertFalse($range->in('c1'));

        // should include 'to'
        $this->assertTrue($range->in('c3'));
    }
}
