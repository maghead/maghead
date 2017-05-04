<?php
use Maghead\Testing\ModelTestCase;
use Maghead\Runtime\Config\FileConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\{AllocateShard, CloneShard, RemoveShard, PruneShard};
use Maghead\Utils;
use Maghead\Runtime\Bootstrap;
use Maghead\Schema\SchemaUtils;

/**
 * @group sharding
 */
class PruneShardTest extends StoreTestCase
{
    protected $onlyDriver = 'mysql';

    public function testPruneShard()
    {
        $o = new AllocateShard($this->config);
        $o->allocate('M_store_id', 'local', 's4');

        Bootstrap::setupDataSources($this->config, $this->dataSourceManager);

        $this->assertInsertStores(static::$stores);
        $this->assertInsertOrders(static::$orders);

        $schemas = SchemaUtils::findSchemasByConfig($this->config);

        $o = new PruneShard($this->config);
        $o->prune('M_store_id', $schemas);

        $o = new RemoveShard($this->config);
        $o->remove('M_store_id', 's4');
    }
}
