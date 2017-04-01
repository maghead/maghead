<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\AllocateShard;
use Maghead\Sharding\Operations\RemoveShard;

/**
 * @group sharding
 */
class AllocateShardTest extends StoreTestCase
{
    public $onlyDriver = 'mysql';

    public function config()
    {
        return ConfigLoader::loadFromFile("tests/apps/StoreApp/config_mysql.yml");
    }

    /**
     * @rebuild false
     */
    public function testAllocateShard()
    {
        $o = new AllocateShard($this->config, $this->logger);
        $o->allocate('local', 't1');
    }

    /**
     * @depends testAllocateShard
     * @rebuild false
     */
    public function testRemoveShard()
    {
        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('local', 't1');
    }
}
