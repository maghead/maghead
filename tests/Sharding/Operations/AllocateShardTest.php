<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

use Maghead\Sharding\Operations\AllocateShard;
use Maghead\Sharding\Operations\CloneShard;
use Maghead\Sharding\Operations\RemoveShard;

use Maghead\Utils;

/**
 * @group sharding
 */
class AllocateShardTest extends StoreTestCase
{
    protected $onlyDriver = 'mysql';

    protected $freeConnections = false;

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

        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('t1');
    }

    /**
     * @depends testAllocateShard
     * @rebuild false
     */
    public function testCloneShard()
    {
        if (false === Utils::findBin('mysqldbcopy')) {
            return $this->markTestSkipped('mysql-utilities is not installed.');
        }
        $o = new CloneShard($this->config, $this->logger);
        $o->setDropFirst(true);
        $o->clone('node_master', 't2');

        $o = new RemoveShard($this->config, $this->logger);
        $o->remove('t2');
    }
}
