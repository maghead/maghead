<?php

namespace Maghead\Runtime;

use Maghead\Runtime\CollectionPager;

/**
 * @group collection
 */
class CollectionPagerTest extends \PHPUnit\Framework\TestCase
{
    public function testCollectionPager()
    {
        $pager = new CollectionPager(range(1, 200), 1, 10);
        $items = $pager->items();
        $this->assertEquals(1, $items[0]);
        $this->assertEquals(2, $items[1]);

        $pager->next();
        $items = $pager->items();
        $this->assertEquals(11, $items[0]);
        $this->assertEquals(12, $items[1]);

        $pager->next();
        $items = $pager->items();
        $this->assertEquals(21, $items[0]);
        $this->assertEquals(22, $items[1]);

        $pager->previous();
        $items = $pager->items();
        $this->assertEquals(11, $items[0]);
        $this->assertEquals(12, $items[1]);

        $this->assertEquals(20, $pager->pages());
    }
}
