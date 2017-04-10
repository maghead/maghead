<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\{AllocateShard, CloneShard, RemoveShard, PruneShard};
use Maghead\Utils;
use Maghead\Bootstrap;

/**
 * @group sharding
 */
class PruneShardTest extends StoreTestCase
{
    protected $onlyDriver = 'mysql';

    public function testPruneShard()
    {
        $o = new AllocateShard($this->config, $this->logger);
        $o->allocate('local', 's4', 'M_store_id');

        Bootstrap::setupDataSources($this->config, $this->dataSourceManager);

        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        $o = new PruneShard($this->config, $this->logger);
        $o->prune('node1', 'M_store_id');
        $o->prune('node2', 'M_store_id');
        $o->prune('node3', 'M_store_id');
        $o->prune('s4', 'M_store_id');

        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('s4');
    }
}
