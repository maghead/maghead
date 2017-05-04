<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
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
        list($ret, $sql) = $dbManager->drop($node['database']);
        $this->logger->debug($sql);

        $this->logger->info("Database $nodeId is dropped successfully.");
    }
}
