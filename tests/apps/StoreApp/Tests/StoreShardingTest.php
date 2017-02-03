<?php
use Maghead\Testing\ModelTestCase;
use Maghead\ConfigLoader;
use StoreApp\Model\Store;
use StoreApp\Model\StoreCollection;
use StoreApp\Model\StoreSchema;
use StoreApp\Model\Order;
use StoreApp\Model\OrderSchema;
use StoreApp\Model\OrderCollection;

class StoreShardingTest extends ModelTestCase
{
    protected $defaultDataSource = 'node1';

    public function getModels()
    {
        return [new StoreSchema, new OrderSchema];
    }

    protected function loadConfig()
    {
        $config = ConfigLoader::loadFromArray([
            'cli' => ['bootstrap' => 'vendor/autoload.php'],
            'schema' => array (
                    'auto_id' => true,
                    'base_model' => '\\Maghead\\BaseModel',
                    'base_collection' => '\\Maghead\\BaseCollection',
                    'paths' => 
                    array (
                    0 => 'tests',
                    ),
                ),
            'cache' => array (
                'class' => 'Maghead\\Cache\\Memcache',
                'servers' => 
                array (
                0 => 
                array (
                    'host' => 'localhost',
                    'port' => 11211,
                ),
                ),
            ),
            'data_source' => [
                'default' => 'node1',
                'nodes' => [
                    'node1' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'user' => NULL,
                        'pass' => NULL,
                        'connection_options' => [],
                    ],
                ],
            ],
        ]);
        // $config->setDefaultDataSourceId('sqlite');
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
            $store = Store::defaultRepo()->loadByCode($storeCode);

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
