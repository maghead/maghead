<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\Manager\ConnectionManager;
use Maghead\Sharding\Manager\ConfigManager;
use Maghead\Sharding\Manager\ShardManager;
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

        $dataSourceManager = DataSourceManager::getInstance();

        // Create the instance connection manager
        $connectionManager = new ConnectionManager($config->getInstances());
        $conn = $connectionManager->connectInstance($this->options->instance);
        $newnode = $connectionManager->getNodeConfig($this->options->instance);
        $newnode['database'] = $nodeId;
        $newnode = DSN::update($newnode);
        $dataSourceManager->addNode($nodeId, $newnode);

        $shardManager = new ShardManager($config, $dataSourceManager);
        $mapping = $shardManager->loadShardMapping($this->options->mapping);
        $mapping->addShardId($nodeId);

        $configManager = new ConfigManager($config);
        $configManager->addShardMapping($mapping);
        $configManager->addDatabaseConfig($nodeId, $newnode);
        $configManager->save();

        // create the database
        $create = $this->createCommand('Maghead\\Command\\DbCommand\\CreateCommand');
        $create->execute($nodeId);
    }
}
