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

    public function testPruneShard()
    {
        $o = new AllocateShard($this->config, $this->logger);
        $o->allocate('local', 's4', 'M_store_id');

        // TODO: insert store and order data
        foreach ($this->storeDataProvider() as $args) {
            call_user_func_array([$this,'assertInsertStores'], $args);
        }

        foreach ($this->orderDataProvider() as $args) {
            call_user_func_array([$this,'assertInsertOrders'], $args);
        }

        // Run prune
        // $o = new PruneShard($this->config, $this->logger);
        // $o->prune('t1', 'M_store_id');

        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('s4');
    }
}
