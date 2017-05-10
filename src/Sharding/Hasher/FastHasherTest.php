<?php

use Maghead\Sharding\Hasher\FastHasher;
use Maghead\Sharding\ShardMapping;
use Maghead\Manager\DataSourceManager;

use StoreApp\StoreTestCase;

/**
 * @group sharding
 */
class FastHasherTest extends StoreTestCase
{
    protected $mapping;

    public function setUp()
    {
        parent::setUp();
        $this->mapping = new ShardMapping('M_store_id', [
            'key' => 'store_id',
            'shards' => ['node1', 'node2', 'node3'],
            'chunks' => [
                ["from" => 0,          "index" => 536870912,  "shard" =>  "node1" ],
                ["from" => 536870912,  "index" => 1073741824, "shard" =>  "node1" ],
                ["from" => 1073741824, "index" => 1610612736, "shard" =>  "node1" ],
                ["from" => 1610612736, "index" => 2147483648, "shard" =>  "node2" ],
                ["from" => 2147483648, "index" => 2684354560, "shard" =>  "node2" ],
                ["from" => 2684354560, "index" => 3221225472, "shard" =>  "node2" ],
                ["from" => 3221225472, "index" => 3758096384, "shard" =>  "node3" ],
                ["from" => 3758096384, "index" => 4294967296, "shard" =>  "node3" ],
            ]
        ], $this->dataSourceManager);
    }

    public function testGetBuckets()
    {
        $hasher = new FastHasher($this->mapping);
        $buckets = $hasher->getBuckets();
        $this->assertEquals([
            536870912 => 0,
            1073741824 => 1,
            1610612736 => 2,
            2147483648 => 3,
            2684354560 => 4,
            3221225472 => 5,
            3758096384 => 6,
            4294967296 => 7
        ], $buckets);
    }



    public function lookupKeyProvider()
    {
        return [
            [30, 2473281379,   4],
            [40, 3693793700,   6],
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
            ['c2.5' , 3 , 1610612736 , 1921809152] , // migrate c3 to c2.5 with range 1591159457 ~ 1921809152
            ['c4'   , 1 , 536870912  , 784195118]  , // migrate c3 to c2.5 with range 1591159457 ~ 1921809152
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
