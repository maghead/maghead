<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
use Maghead\DSN\DSNParser;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\DropDatabaseQuery;


use PDO;

class DropCommand extends BaseCommand
{
    public function brief()
    {
        return 'drop database base on the database config.';
    }

    public function execute($nodeId)
    {
        $config = $this->getConfig(true);
        $dataSourceManager = DataSourceManager::getInstance();
        $conn = $dataSourceManager->connectInstance($nodeId);

        $node = $config->getDataSource($nodeId);

        $dbManager = new DatabaseManager($conn);
        list($ret, $sql) = $dbManager->drop($node['database']);
        $this->logger->debug($sql);

        $this->logger->info('Database dropped successfully.');
    }
}
