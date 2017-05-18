<?php
use Magsql\Universal\Query\SelectQuery;
use Maghead\Testing\ModelTestCase;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\Sharding\ShardDispatcher;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

/**
 * @group pthread
 * @group sharding
 */
class PthreadQueryMapperTest extends StoreTestCase
{
    public function setUp()
    {
        if (!extension_loaded('pthreads')) {
            return $this->markTestSkipped('require pthreads extension with zts');
        }
        parent::setUp();
    }

    public function testPthreadQueryWorkerStartThenShutdown()
    {
        $w = new PthreadQueryWorker('sqlite::memory:');
        $w->start();
        $w->shutdown();
    }

    public function testJoinJobResults()
    {
        $shardManager = new ShardManager($this->config, $this->dataSourceManager);

        $mapping = $shardManager->loadShardMapping('M_store_id');

        $shards = $shardManager->loadShardCollectionOf('M_store_id');

        $this->assertNotEmpty($shards);

        $dispatcher = new ShardDispatcher($mapping, $shards);

        $g1 = $shards['node1'];
        $repo1 = $g1->createRepo('StoreApp\\Model\\OrderRepo');
        $this->assertInstanceOf('Maghead\\Runtime\\Repo', $repo1);

        $g2 = $shards['node2'];
        $repo2 = $g2->createRepo('StoreApp\\Model\\OrderRepo');
        $this->assertInstanceOf('Maghead\\Runtime\\Repo', $repo2);

        $ret = $repo1->create(['store_id' => 1 , 'amount' => 200]);
        $this->assertResultSuccess($ret);
        $o1 = $repo1->findByPrimaryKey($ret->key);
        $this->assertNotNull($o1);

        $ret = $repo2->create(['store_id' => 2 , 'amount' => 1000]);
        $this->assertResultSuccess($ret);
        $o2 = $repo2->findByPrimaryKey($ret->key);
        $this->assertNotNull($o2);

        $query = new SelectQuery;
        $query->select(['SUM(amount)' => 'amount']);
        $query->from('orders');

        $mapper = new PthreadQueryMapper($this->dataSourceManager);
        $results = $mapper->map($shards, $query);

        $total = 0;
        foreach ($results as $nodeId => $rows) {
            $total += intval($rows[0]['amount']);
        }
        $this->assertEquals(1200, $total);
    }
}
