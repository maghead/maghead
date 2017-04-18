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

    public function testKeysOf()
    {
        $hasher = new FastHasher($this->mapping);
        $indexes = $hasher->keysOf('c1');
        $this->assertEquals([1591159457], $indexes);

    }


    public function testGetBuckets()
    {
        $hasher = new FastHasher($this->mapping);
        $buckets = $hasher->getBuckets();
        $this->assertEquals([
            1591159457 => 'c1',
            2967030669 => 'c3',
            3353246491 => 'c2',
        ], $buckets);
    }



    public function lookupKeyProvider()
    {
        return [
            [30, 2473281379,   'c3'],
            [40, 3693793700,   'c1'],
        ];
    }



    /**
     * @dataProvider lookupKeyProvider
     */
    public function testLookup($key, $hash, $node)
    {
        $hasher = new FastHasher($this->mapping);
        $h = $hasher->hash($key);
        $this->assertEquals($hash, $h);

        $n = $hasher->lookup($key);
        $this->assertEquals($node, $n);
    }


    public function rangeTestDataProvider()
    {
        return [
            /* newNode, nextNode, from, index */
            ['c2.5', 'c3', 1591159457, 1921809152], // migrate c3 to c2.5 with range 1591159457 ~ 1921809152
            ['c4', 'c1', 0, 784195118], // migrate c3 to c2.5 with range 1591159457 ~ 1921809152
        ];
    }

    /**
     * As the shard split action runner,
     * I want to lookup the range between the key of the new target and the previous target
     * So that we can migrate the data from the existing node to the new node.
     *
     * @dataProvider rangeTestDataProvider
     * @depends testLookup
     */
    public function testLookupRange($newNode, $nextNode, $from, $index)
    {
        $hasher = new FastHasher($this->mapping);
        $range = $hasher->lookupRange($newNode);
        $this->assertNotNull($range, 'always return range');
        $this->assertInstanceOf('Maghead\Sharding\Hasher\HashRange', $range);
        $this->assertEquals($from, $range->from);
        $this->assertEquals($index, $range->index);

        $n = $hasher->lookup($newNode);
        $this->assertEquals($nextNode, $n, 'the next should be c3');

        // then we can migrate data 
        // from "c3" where $from < $key <= $index 
        // to "c2.5"
        return $range;
    }

    public function testRangeIn()
    {
        $hasher = new FastHasher($this->mapping);
        $range = $hasher->lookupRange('c2.5');
        $this->assertTrue($range->in('c2.5'), 'index itself should be included.');
        $this->assertFalse($range->in('c1'), 'should not include "from"');
    }
}
