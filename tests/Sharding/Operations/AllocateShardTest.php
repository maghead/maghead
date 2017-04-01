<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

/**
 * @group sharding
 */
class AllocateShardTest extends StoreTestCase
{
    public function config()
    {
        return ConfigLoader::loadFromFile("tests/apps/StoreApp/config_sqlite_file.yml");
    }

    public function testAllocateShard()
    {


    }
}
