<?php
use Maghead\Testing\ModelTestCase;
use Maghead\Runtime\Config\FileConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\{AllocateShard, CloneShard, RemoveShard, PruneShard};
use Maghead\Utils;

/**
 * @group sharding
 */
class CloneShardTest extends StoreTestCase
{
    protected $onlyDriver = 'mysql';

    /**
     * @depends testAllocateShard
     * @rebuild false
     */
    public function testCloneShard()
    {
        if (false === Utils::findBin('mysqldbcopy')) {
            return $this->markTestSkipped('mysql-utilities is not installed.');
        }

        $this->expectOutputRegex('/Copying data/');

        $o = new CloneShard($this->config);
        $o->setDropFirst(true);
        $o->clone('M_store_id', 'local', 't2', 'master');

        $o = new RemoveShard($this->config);
        $o->remove('M_store_id', 't2');
    }
}
