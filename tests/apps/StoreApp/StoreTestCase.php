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

    protected function config()
    {
        return ConfigLoader::loadFromFile("tests/apps/StoreApp/config_sqlite_file.yml");
    }
}
