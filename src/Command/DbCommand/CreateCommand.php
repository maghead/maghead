<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\DatabaseManager;
use Maghead\Manager\DataSourceManager;
use Maghead\DSN\DSNParser;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use PDO;
use Exception;

class CreateCommand extends BaseCommand
{
    public function brief()
    {
        return 'create database bases on the current config.';
    }

    public function execute($nodeId = null)
    {
        $config = $this->getConfig();
        $dsId = $nodeId ?: $this->getCurrentDataSourceId();
        $ds = $config->getDataSource($dsId);

        if ($ds['driver'] === 'sqlite') {
            $this->logger->error('Create database query is not supported by sqlite. ths sqlite database shall have been created.');
            return true;
        }

        if (!isset($ds['dsn'])) {
            throw new Exception("Attribute 'dsn' undefined in data source settings.");
        }

        $dataSourceManager = DataSourceManager::getInstance();
        $conn = $dataSourceManager->connectInstance($dsId);

        $queryDriver = $conn->getQueryDriver();

        $dbManager = new DatabaseManager($conn);
        list($ret, $sql) = $dbManager->create($ds['database'], [
            'charset' => isset($ds['charset']) ? $ds['charset'] : null,
        ]);
        if ($ret) {
            $this->logger->info("Succeed: $sql");
            $this->logger->info('Database created successfully.');
        } else {
            $this->logger->info("Failed: $sql");
            $this->logger->info("Failed to create database $dbName.");
        }
    }
}
