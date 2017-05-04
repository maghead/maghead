<?php

namespace Maghead\Sharding\Operations;

use Maghead\Runtime\Config\Config;
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Sharding\Manager\ShardManager;

class BaseShardOperation
{
    protected $config;

    protected $instanceManager;

    protected $dataSourceManager;

    protected $shardManager;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->instanceManager = new ConnectionManager($config->getInstances());
        $this->dataSourceManager = new DataSourceManager($config->getDataSources());
        $this->shardManager = new ShardManager($this->config, $this->dataSourceManager);
    }
}
