<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ConnectionManager;
use Maghead\Manager\ConfigManager;
use Maghead\DSN\DSN;
use PDO;
use Exception;

class AllocateCommand extends BaseCommand
{
    public function brief()
    {
        return 'allocate a shard';
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('mapping:', 'the shard mapping where the new shard will be added to.');
        $opts->add('instance:', 'the instance id')
            ->defaultValue('local');
    }

    public function arguments($args)
    {
        $args->add('node-id');
        $args->add('dsn');
    }

    public function execute($nodeId)
    {
        $shardId = $nodeId;
        $config = $this->getConfig(true);

        // Create the instance connection manager
        $connectionManager = new ConnectionManager($config->getInstances());
        $conn = $connectionManager->connectInstance($this->options->instance);
        $newnode = $connectionManager->getNodeConfig($this->options->instance);
        $newnode['database'] = $nodeId;
        $newnode = DSN::update($newnode);

        $configManager = new ConfigManager($config);
        $configManager->addDatabaseConfig($nodeId, $newnode);
        var_dump($newnode);
    }
}
