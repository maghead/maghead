<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\Store;
use StoreApp\Model\StoreCollection;
use StoreApp\Model\StoreSchema;
use StoreApp\Model\Order;
use StoreApp\Model\OrderSchema;
use StoreApp\Model\OrderCollection;

/**
 * @group app
 * @group sharding
 */
class StoreShardingTest extends ModelTestCase
{
    protected $defaultDataSource = 'node1';

    protected $requiredDataSources = ['node1', 'node1_2', 'node2', 'node2_2'];

    public function getModels()
    {
        return [
            new StoreSchema,
            new OrderSchema
        ];
    }

    protected function loadConfig()
    {
        $config = ConfigLoader::loadFromArray([
            'cli' => ['bootstrap' => 'vendor/autoload.php'],
            'schema' => [
                'auto_id' => true,
                'base_model' => '\\Maghead\\Runtime\\BaseModel',
                'base_collection' => '\\Maghead\\BaseCollection',
                'paths' => ['tests'],
            ],
            'sharding' => [
                'mappings' => [
                    // shard by hash
                    'M_store_id' => [
                        'tables' => ['orders'], // This is something that we will define in the schema.
                        'shards' => [ 's1', 's2' ],
                        'key' => 'store_id',
                        'chunks' => [
                            'c1' => [ 'shard' => 's1' ],
                            'c2' => [ 'shard' => 's2' ],
                        ],
                        'hash' => [
                            'target1' => 'c1',
                            'target2' => 'c2',
                        ],
                    ],
                    'M_created_at' => [
                        'key' => 'created_at',
                        'tables' => ['orders'], // This is something that we will define in the schema.
                        'range' => [
                            's1' => [ 'min' => 0, 'max' => 10000 ],
                            's2' => [ 'min' => 10001, 'max' => 20000 ],
                        ]
                    ],
                ],
                // Shards pick servers from nodes config, HA groups
                'shards' => [
                    's1' => [
                        'write' => [
                          'node1_2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node1'   =>  ['weight' => 0.1],
                          'node1_2' => ['weight' => 0.1],
                        ],
                    ],
                    's2' => [
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
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node1_2' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node2' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node2_2' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                ],
            ],
        ]);
        // $config->setMasterDataSourceId('sqlite');
        // $config->setAutoId();
        return $config;
    }

    public function orderDataProvider()
    {
        $orders = [];
        $orders['TW001'] = [
            [ 'amount' => 100 ],
            [ 'amount' => 100 ],
            [ 'amount' => 100 ],
            [ 'amount' => 100 ],
        ];
        $orders['TW002'] = [
            [ 'amount' => 10 ],
            [ 'amount' => 10 ],
            [ 'amount' => 10 ],
            [ 'amount' => 10 ],
        ];
        return [$orders];
    }

    public function storeDataProvider()
    {
        $stores = [];
        $stores[] = [ 'code' => 'TW001', 'name' => '天仁茗茶 01' ];
        $stores[] = [ 'code' => 'TW002', 'name' => '天仁茗茶 02' ];
        $stores[] = [ 'code' => 'TW003', 'name' => '天仁茗茶 03' ];
        return [[$stores]];
    }

    /**
     * @dataProvider storeDataProvider
     */
    public function testCreateStoresGlobally($storeArgs)
    {
        foreach ($storeArgs as $args) {
            $ret = Store::create($args);
            $this->assertResultSuccess($ret);
        }

        $orderData = $this->orderDataProvider();
        foreach ($orderData[0] as $storeCode => $ordersData) {
            $store = Store::masterRepo()->loadByCode($storeCode);

            // create orders
            foreach ($ordersData as $orderData) {
                $orderData['store_id'] = $store->id;
                $ret = Order::create($orderData);
                $this->assertResultSuccess($ret);
            }
        }
        // all orders ready
    }
}
