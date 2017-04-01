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

    protected $requiredDataSources = ['node_master', 'node1', 'node2'];

    public function models()
    {
        return [new StoreSchema, new OrderSchema];
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
            'sharding' => \StoreApp\Config::sharding(),
            // 'data_source' => \StoreApp\Config::memory_data_source(),
            'data_source' => \StoreApp\Config::data_source(),
        ]);
        return $config;
    }
}
