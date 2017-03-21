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
            new OrderSchema,
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
    public function testStoreGlobalCRUD($storeArgs)
    {
        foreach ($storeArgs as $args) {
            $ret = Store::create($args);
            $this->assertResultSuccess($ret);

            $store = Store::findByPrimaryKey($ret->key);
            $this->assertNotNull($store);

            $ret = $store->update([ 'name' => $args['name'] . ' U' ]);
            $this->assertResultSuccess($ret);

            $ret = $store->delete();
            $this->assertResultSuccess($ret);
        }
    }

    /**
     * @depends testStoreGlobalCRUD
     * @dataProvider storeDataProvider
     */
    public function testStoreGlobalCreate($storeArgs)
    {
        foreach ($storeArgs as $args) {
            $ret = Store::create($args);
            $this->assertResultSuccess($ret);
        }
    }


    /**
     * @rebuild false
     * @depends testStoreGlobalCreate
     * @dataProvider orderDataProvider
     */
    public function testOrderCRUDInShards($orderArgsList)
    {
        $orders = [];
        foreach ($orderArgsList as $storeCode => $storeOrderArgsList) {
            $store = Store::masterRepo()->findByCode($storeCode);
            $this->assertNotFalse($store, 'load store by code');

            foreach ($storeOrderArgsList as $orderArgs) {
                $orderArgs['store_id'] = $store->id;
                $ret = Order::create($orderArgs); // should dispatch the shards by the store_id
                $this->assertResultSuccess($ret);
                $this->assertNotNull($ret->shard);

                $orders[] = $ret->args;
                // printf("Order %s in Shard %s\n", Ramsey\Uuid\Uuid::fromBytes($ret->key), $ret->shard->id); 
            }
        }
        return $orders;
    }

    /**
     * @rebuild false
     * @depends testStoreGlobalCreate
     */
    public function testShardQueryUUID()
    {
        $store = Store::masterRepo()->findByCode('TW002');
        $this->assertNotFalse($store, 'load store by code');
        $shard = Order::shards()->dispatch($store->id);
        $this->assertInstanceOf('Maghead\\Sharding\\Shard', $shard);
        $uuid = $shard->queryUUID();
        $this->assertNotNull($uuid);
    }

    /**
     * @rebuild false
     * @depends testStoreGlobalCreate
     */
    public function testOrderUUIDDeflator()
    {
        $store = Store::masterRepo()->findByCode('TW002');
        $this->assertNotFalse($store, 'load store by code');
        $repo = Order::shards()->dispatch($store->id)->repo(OrderRepo::class);

        $ret = $repo->create([ 'store_id' => $store->id, 'amount' => 20 ]);
        $this->assertResultSuccess($ret);

        $order = $repo->findByPrimaryKey($ret->key);
        $this->assertNotNull($order);
        $this->assertNotNull($order->uuid);
        $this->assertInstanceOf('Ramsey\Uuid\Uuid', $order->getUuid(), 'returned uuid should be an UUID object.');
    }

    /**
     * @rebuild false
     * @depends testStoreGlobalCreate
     */
    public function testInsertOrder()
    {
        $store = Store::masterRepo()->findByCode('TW002');
        $this->assertNotFalse($store, 'load store by code');

        $ret = Order::shards()->dispatch($store->id)
            ->repo(OrderRepo::class)
            ->create([
                'store_id' => $store->id,
                'amount' => 20,
            ]);

        $this->assertResultSuccess($ret);
        $this->assertNotNull($ret->key);
        return $ret;
    }


    /**
     * @rebuild false
     * @depends testInsertOrder
     */
    public function testFindOrderByPrimaryKeyInTheShard($orderRet)
    {
        $order = Order::findByPrimaryKey($orderRet->key);
        $this->assertNotNull($order);
        $this->assertInstanceOf('Maghead\\Runtime\\BaseModel', $order);
        $this->assertEquals($orderRet->key, $order->getKey());
        $this->assertEquals($orderRet->key, $order->uuid, 'key is uuid');
        return $orderRet;
    }

    /**
     * @rebuild false
     * @depends testFindOrderByPrimaryKeyInTheShard
     */
    public function testOrderAmountUpdateShouldUpdateToTheSameRepository($orderRet)
    {
        $order = Order::findByPrimaryKey($orderRet->key);
        $ret = $order->update([ 'amount' => 9999 ]);
        $this->assertResultSuccess($ret);
        $this->assertEquals(9999, $order->amount);
        $this->assertNotNull($order->repo, 'BaseModel should be have the repo object.');

        $order2 = Order::findByPrimaryKey($order->getKey());
        $this->assertEquals(9999, $order2->amount);

        return $orderRet;
    }



    /**
     * @rebuild false
     * @depends testOrderAmountUpdateShouldUpdateToTheSameRepository
     */
    public function testOrderDeleteShouldDeleteToTheSameRepo($orderRet)
    {
        // reload the order from the repo
        $order = Order::findByPrimaryKey($orderRet->key);
        $ret = $order->delete();
        $this->assertResultSuccess($ret);

        $order2 = Order::findByPrimaryKey($orderRet->key);
        $this->assertNull($order2);
    }


}
