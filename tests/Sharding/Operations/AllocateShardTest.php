<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\AllocateShard;

/**
 * @group sharding
 */
class AllocateShardTest extends StoreTestCase
{
    public function config()
    {
        return ConfigLoader::loadFromFile("tests/apps/StoreApp/config_mysql.yml");
    }

    public function testAllocateShard()
    {
        $o = new AllocateShard($this->config);
        $o->allocate('local', 't1');
    }
}
