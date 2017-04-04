<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\{AllocateShard, CloneShard, RemoveShard, PruneShard};
use Maghead\Utils;

/**
 * @group sharding
 */
class AllocateShardTest extends StoreTestCase
{
    protected $onlyDriver = 'mysql';

    /**
     * @rebuild false
     */
    public function testAllocateShard()
    {
        $o = new AllocateShard($this->config, $this->logger);
        $o->allocate('local', 't1', 'M_store_id');

        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('t1');
    }
}
