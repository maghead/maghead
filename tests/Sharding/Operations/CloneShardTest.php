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

        $o = new CloneShard($this->config, $this->logger);
        $o->setDropFirst(true);
        $o->clone('local', 't2', 'node_master');

        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('t2');
    }
}
