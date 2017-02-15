<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Testing\ModelTestCase;
use Maghead\Sharding\QueryMapper\Gearman\GearmanQueryMapper;
use Maghead\ConfigLoader;
use Maghead\Manager\ShardManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};


/**
 * @group sharding
 */
class GearmanQueryMapperTest extends ModelTestCase
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
        if (!extension_loaded('gearman')) {
            return $this->markTestSkipped('require gearman extension');
        }
        parent::setUp();
    }

    public function testGearmanQueryMapper()
    {
        $shardManager = new ShardManager($this->config, $this->connManager);
        $mapping = $shardManager->getShardMapping('M_store_id');
        $shards = $shardManager->getShardsOf('M_store_id');
        $this->assertNotEmpty($shards);

        $query = new SelectQuery;
        $query->select(['SUM(amount)' => 'amount']);
        $query->from('orders');
        // $query->where()->equal('store_id', [2,3,4]);

        $client = new GearmanClient;
        $client->addServer();
        $mapper = new GearmanQueryMapper($client);
        $mapper->map($shards, $query);
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
