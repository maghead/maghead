<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\Store;
use StoreApp\Model\StoreCollection;
use StoreApp\Model\StoreSchema;
use StoreApp\Model\Order;
use StoreApp\Model\OrderRepo;
use StoreApp\Model\OrderSchema;
use StoreApp\Model\OrderCollection;

/**
 * @group app
 * @group sharding
 */
class StoreShardingTest extends ModelTestCase
{
    protected $defaultDataSource = 'node_master';

    protected $requiredDataSources = ['node_master','node1', 'node2'];

    protected $freeConnections = false;

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
                    'M_store_id' => \StoreApp\Model\StoreShardMapping::config(),
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
                          'node1' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node1'   =>  ['weight' => 0.1],
                        ],
                    ],
                    's2' => [
                        'write' => [
                          'node2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node2'   =>  ['weight' => 0.1],
                        ],
                    ],
                ],
            ],
            // data source is defined for different data source connection.
            'data_source' => \StoreApp\Config::memory_data_source(),
        ]);
        // $config->setMasterDataSourceId('sqlite');
        // $config->setAutoId();
        return $config;
    }

    public function orderDataProvider()
    {
        $orders = [];
        $orders['TW001'] = [
            [ 'amount' => 100, 'paid' => false ],
            [ 'amount' => 100, 'paid' => false ],
            [ 'amount' => 100, 'paid' => false ],
            [ 'amount' => 100, 'paid' => false ],
        ];
        $orders['TW002'] = [
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
        ];
        $orders['TW003'] = [
            [ 'amount' => 1000, 'paid' => false ],
            [ 'amount' => 1000, 'paid' => false ],
            [ 'amount' => 1000, 'paid' => false ],
            [ 'amount' => 1000, 'paid' => false ],
        ];
        return [[$orders]];
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
    }



    /**
     * @rebuild false
     * @depends testCreateStoresGlobally
     * @dataProvider orderDataProvider
     */
    public function testInsertOrderIntoShards($orderArgsList)
    {
        foreach ($orderArgsList as $storeCode => $storeOrderArgsList) {
            $store = Store::masterRepo()->loadByCode($storeCode);
            $this->assertNotFalse($store, 'load store by code');
            foreach ($storeOrderArgsList as $orderArgs) {
                $orderArgs['store_id'] = $store->id;
                $ret = Order::create($orderArgs);
                $this->assertResultSuccess($ret);
            }
        }
    }

    /**
     * @rebuild false
     * @depends testCreateStoresGlobally
     */
    public function testInsertOrderFromShardCollectionDispatch()
    {
        $store = Store::masterRepo()->loadByCode('TW002');
        $this->assertNotFalse($store, 'load store by code');
        $ret = Order::shards()->dispatch($store->id)
            ->repo('StoreApp\\Model\\OrderRepo')
            ->create([ 'store_id' => $store->id, 'amount' => 20 ]);
        $this->assertResultSuccess($ret);
    }

    /**
     * @rebuild false
     * @depends testInsertOrderFromShardCollectionDispatch
     */
    public function testFindOrderWithSharding()
    {
        $store = Store::masterRepo()->loadByCode('TW002');
        $this->assertNotFalse($store, 'load store by code');
    }
}
