<?php
require 'vendor/autoload.php';

use Maghead\Sharding\QueryMapper\Gearman\GearmanQueryWorker;
use Maghead\Manager\ConnectionManager;
use Maghead\Runtime\Config\Config;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;

use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Runtime\Bootstrap;

$config = ConfigLoader::loadFromArray([
    'cli' => ['bootstrap' => 'vendor/autoload.php'],
    'schema' => [
        'auto_id' => true,
        'base_record' => '\\Maghead\\Runtime\\Model',
        'base_collection' => '\\Maghead\\Runtime\\Collection',
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
Bootstrap::setup($config); // setup connection manager

// create a log channel
$logger = new Logger('worker');
$logger->pushHandler(new StreamHandler('worker.log', Logger::DEBUG));
$logger->pushHandler(new ErrorLogHandler(), Logger::DEBUG);

$worker = new GearmanQueryWorker($config, ConnectionManager::getInstance(), null, $logger);
$worker->run();
