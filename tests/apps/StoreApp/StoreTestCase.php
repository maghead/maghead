<?php
namespace StoreApp;

use Maghead\Testing\ModelTestCase;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Manager\DataSourceManager;

use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use Maghead\Sharding\Manager\ConfigManager;

use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};

abstract class StoreTestCase extends ModelTestCase
{
    protected $defaultDataSource = 'master';

    protected $requiredDataSources = ['master', 'node1', 'node2', 'node3'];

    // FIXME: pgsql doesn't support UUID binary(32), need to find a way to support it.
    protected $skipDriver = 'pgsql';

    protected $shardManager;

    public static $stores = [
        [ 'code' => 'TW001', 'name' => '天仁茗茶 01' ],
        [ 'code' => 'TW002', 'name' => '天仁茗茶 02' ],
        [ 'code' => 'TW003', 'name' => '天仁茗茶 03' ],

        [ 'code' => 'CC001', 'name' => 'Coco 01' ],
        [ 'code' => 'CC002', 'name' => 'Coco 02' ],
        [ 'code' => 'CC003', 'name' => 'Coco 03' ],

        [ 'code' => 'D01', 'name' => 'D1' ],
        [ 'code' => 'D02', 'name' => 'D2' ],
        [ 'code' => 'D03', 'name' => 'D3' ],
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
        ],
        'CC001' => [
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
        ],
        'CC002' => [
            [ 'amount' => 20, 'paid' => false ],
            [ 'amount' => 20, 'paid' => false ],
            [ 'amount' => 20, 'paid' => false ],
            [ 'amount' => 20, 'paid' => false ],
            [ 'amount' => 20, 'paid' => false ],
        ],
        'CC003' => [
            [ 'amount' => 50, 'paid' => false ],
            [ 'amount' => 50, 'paid' => false ],
        ],
        'D01' => [
            [ 'amount' => 20, 'paid' => false ],
        ],
        'D02' => [
            [ 'amount' => 10, 'paid' => false ],
            [ 'amount' => 10, 'paid' => false ],
        ],
        'D03' => [
            [ 'amount' => 5, 'paid' => false ],
            [ 'amount' => 5, 'paid' => false ],
            [ 'amount' => 5, 'paid' => false ],
            [ 'amount' => 5, 'paid' => false ],
        ],
    ];

    public function models()
    {
        return [
            new StoreSchema,
            new OrderSchema,
        ];
    }

    public function setUp()
    {
        parent::setUp();

        $mapping = new ShardMapping('M_store_id',
            [ 'key' => 'store_id', 'shards' => ['node1', 'node2', 'node3'], 'hash' => true, 'chunks' => [] ],
            $this->dataSourceManager
        );

        $chunkManager = new ChunkManager($mapping);
        $chunkManager->distribute(['node1', 'node2', 'node3'], 8);

        $configManager = new ConfigManager($this->config);
        $configManager->setShardMapping($mapping);

        // $this->assertTrue($ret->isAcknowledged());
        // $configManager->save($configFile);

        $this->shardManager = new ShardManager($this->config, $this->dataSourceManager);
    }

    protected function config()
    {
        $driver = $this->getCurrentDriverType();
        $configFile = __DIR__ . "/config/{$driver}.yml";
        $tmpConfig = tempnam("/tmp", "{$driver}_") . '.yml';
        if (false === copy($configFile, $tmpConfig)) {
            throw new \Exception("failed to copy the config file: $configFile");
        }
        return FileConfigLoader::load($tmpConfig, true);
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
        foreach ($storeArgs as $args) {
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
