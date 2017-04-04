<?php
namespace StoreApp;

use Maghead\Testing\ModelTestCase;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};

abstract class StoreTestCase extends ModelTestCase
{
    protected $defaultDataSource = 'node_master';

    protected $requiredDataSources = ['node_master', 'node1', 'node2', 'node3'];

    protected $skipDriver = 'pgsql';

    public static $stores = [
        [ 'code' => 'TW001', 'name' => '天仁茗茶 01' ],
        [ 'code' => 'TW002', 'name' => '天仁茗茶 02' ],
        [ 'code' => 'TW003', 'name' => '天仁茗茶 03' ],
    ];

    public static $orders = [
        'TW001' => [
            [ 'amount' => 100, 'paid' => false ],
            [ 'amount' => 100, 'paid' => false ],
            [ 'amount' => 100, 'paid' => false ],
            [ 'amount' => 100, 'paid' => false ],
        ],
        'TW002' => [
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
        ],
        'TW003' => [
            [ 'amount' => 1000, 'paid' => false ],
            [ 'amount' => 1000, 'paid' => false ],
            [ 'amount' => 1000, 'paid' => false ],
            [ 'amount' => 1000, 'paid' => false ],
        ]
    ];

    public function models()
    {
        return [
            new StoreSchema,
            new OrderSchema,
        ];
    }

    protected function config()
    {
        $driver = $this->getCurrentDriverType();
        return ConfigLoader::loadFromFile("tests/apps/StoreApp/config/{$driver}.yml", true);
    }

    public function orderDataProvider()
    {
        return [[static::$orders]];
    }

    public function storeDataProvider()
    {
        return [[static::$stores]];
    }

    public function assertCreateStore(array $args)
    {
        $ret = Store::create($args);
        $this->assertResultSuccess($ret);
    }

    public function assertCreateOrder(array $args)
    {
        $ret = Order::create($args); // should dispatch the shards by the store_id
        $this->assertResultSuccess($ret);
        $this->assertNotNull($ret->shard);
    }

    public function assertInsertStores(array $storeArgs)
    {
        foreach ($storeArgs as $code => $args) {
            $ret = Store::create($args);
            $this->assertResultSuccess($ret);
        }
    }

    public function assertInsertOrders(array $orderArgsList)
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
}
