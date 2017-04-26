<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\DatabaseManager;
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
        $opts->add('instance:', 'the instance id');
    }

    public function arguments($args)
    {
        $args->add('node-id');
        $args->add('dsn');
    }

    public function execute($nodeId)
    {
        $config = $this->getConfig(true);
        $databaseManager = DatabaseManager::getInstance();

        $conn = $databaseManager->connectInstance($this->options->instance);

        $configManager = new ConfigManager($config);
        $nodeConfig = $configManager->addDatabase($nodeId, $dsnStr, [
            'host'     => $this->options->host,
            'port'     => $this->options->port,
            'database' => $this->options->dbname,
            'user'     => $this->options->user,
            'password' => $this->options->password,
        ]);

        // $dsId = $this->getCurrentDataSourceId();
        // $ds = $config->getDataSource($dsId);
    }
}
