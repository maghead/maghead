<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Testing\ModelTestCase;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Manager\ShardManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};

/**
 * @group pthread
 * @group sharding
 */
class PthreadQueryMapperTest extends ModelTestCase
{
    protected $defaultDataSource = 'node1';

    protected $requiredDataSources = ['node1','node2'];

    public function getModels()
    {
        return [
            new StoreSchema,
            new OrderSchema,
        ];
    }

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
        $shardManager = new ShardManager($this->config, $this->connManager);

        $mapping = $shardManager->getShardMapping('M_store_id');

        $shards = $shardManager->getShards('M_store_id');
        $this->assertNotEmpty($shards);

        $dispatcher = $shardManager->createShardDispatcher('M_store_id');

        $g1 = $shards['group1'];
        $repo1 = $g1->createRepo('StoreApp\\Model\\OrderRepo');
        $this->assertInstanceOf('Maghead\\Runtime\\BaseRepo', $repo1);

        $g2 = $shards['group2'];
        $repo2 = $g2->createRepo('StoreApp\\Model\\OrderRepo');
        $this->assertInstanceOf('Maghead\\Runtime\\BaseRepo', $repo2);

        $ret = $repo1->create(['store_id' => 1 , 'amount' => 200]);
        $this->assertResultSuccess($ret);
        $o1 = $repo1->loadByPrimaryKey($ret->key);
        $this->assertNotNull($o1);

        $ret = $repo2->create(['store_id' => 2 , 'amount' => 1000]);
        $this->assertResultSuccess($ret);
        $o2 = $repo2->loadByPrimaryKey($ret->key);
        $this->assertNotNull($o2);

        $query = new SelectQuery;
        $query->select(['SUM(amount)' => 'amount']);
        $query->from('orders');

        $mapper = new PthreadQueryMapper($this->connManager);
        $results = $mapper->map($shards, $query);

        $total = 0;
        foreach ($results as $nodeId => $rows) {
            $total += intval($rows[0]['amount']);
        }
        $this->assertEquals(1200, $total);
    }

    protected function loadConfig()
    {
        $config = ConfigLoader::loadFromArray([
            'cli' => ['bootstrap' => 'vendor/autoload.php'],
            'schema' => [
                'auto_id' => true,
                'base_model' => '\\Maghead\\Runtime\\BaseModel',
                'base_collection' => '\\Maghead\\Runtime\\BaseCollection',
                'paths' => ['tests'],
            ],
            'sharding' => [
                'mappings' => [
                    // shard by hash
                    'M_store_id' => [
                        'tables' => ['orders'], // This is something that we will define in the schema.
                        'key' => 'store_id',
                        'hash' => [
                            'target1' => 'group1',
                            'target2' => 'group2',
                        ],
                    ],
                ],
                // Shards pick servers from nodes config, HA groups
                'shards' => [
                    'group1' => [
                        'write' => [
                          'node1_2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node1'   =>  ['weight' => 0.1],
                          'node1_2' => ['weight' => 0.1],
                        ],
                    ],
                    'group2' => [
                        'write' => [
                          'node2_2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node2'   =>  ['weight' => 0.1],
                          'node2_2' => ['weight' => 0.1],
                        ],
                    ],
                ],
            ],
            // data source is defined for different data source connection.
            'data_source' => [
                'master' => 'node1',
                'nodes' => [
                    'node1' => [
                        'dsn' => 'sqlite:node1.sqlite',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node1_2' => [
                        'dsn' => 'sqlite:node1.sqlite',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node2' => [
                        'dsn' => 'sqlite:node2.sqlite',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node2_2' => [
                        'dsn' => 'sqlite:node2.sqlite',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                ],
            ],
        ]);
        return $config;
    }
}
