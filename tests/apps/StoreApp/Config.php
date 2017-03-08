<?php

namespace StoreApp;

// \StoreApp\Config::sharding()
class Config
{
    static public function memory_data_source()
    {
        return [
            'master' => 'node_master',
            'nodes' => [
                'node_master' => [
                    'dsn' => 'sqlite::memory:',
                    'query_options' => ['quote_table' => true],
                    'driver' => 'sqlite',
                    'connection_options' => [],
                ],
                'node1' => [
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
            ],
        ];
    }

    static public function data_source()
    {
        return [
            'master' => 'node_master',
            'nodes' => [
                'node_master' => [
                    'dsn' => 'sqlite:node_master.sqlite',
                    'query_options' => ['quote_table' => true],
                    'driver' => 'sqlite',
                    'connection_options' => [],
                ],
                'node1' => [
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
            ],
        ];
    }


    static public function sharding()
    {
        return [
            'mappings' => [
                'M_store_id' => \StoreApp\Model\StoreShardMapping::config(),
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
                    'write' => [ 'node2' => ['weight' => 0.1] ],
                    'read' => [ 'node2'   =>  ['weight' => 0.1] ],
                ],
            ],
        ];
    }
}



