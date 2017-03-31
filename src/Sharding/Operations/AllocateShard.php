<?php

namespace Maghead\Sharding\Operations;

use Maghead\Sharding\ShardDispatcher;
use Maghead\Sharding\ShardMapping;
use Maghead\Sharding\Shard;
use Maghead\Sharding\ShardCollection;
use Maghead\Manager\ConnectionManager;
use Maghead\Config;

/**
 * Given an instance ID:
 * 1. Connect to the instance
 * 2. Create a database
 * 3. Initialize the db schema
 */
class AllocateShard
{
    protected $config;

    protected $dataSourceManager;

    public function __construct(Config $config, ConnectionManager $dataSourceManager)
    {
        $this->config = $config;
        $this->dataSourceManager = $dataSourceManager;
    }
}
