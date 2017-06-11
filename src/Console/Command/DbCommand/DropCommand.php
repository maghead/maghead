<?php

namespace Maghead\Console\Command\DbCommand;

use Maghead\Console\Command\BaseCommand;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;

class DropCommand extends BaseCommand
{
    public function brief()
    {
        return 'drop database base on the database config.';
    }

    public function execute($nodeId = 'master')
    {
        $config = $this->getConfig();
        $conn = $this->dataSourceManager->connectInstance($nodeId);

        $node = $config->getDataSource($nodeId);

        $dbManager = new DatabaseManager($conn);

        if (!isset($node['database'])) {
            $this->logger->error("'database' is not set.");
            return false;
        }
        
        list($ret, $sql) = $dbManager->drop($node['database']);
        $this->logger->debug($sql);

        $this->logger->info("Database $nodeId is dropped successfully.");
    }
}
