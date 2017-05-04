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
        return 'create database base on the database config.';
    }

    public function execute($nodeId = 'master')
    {
        $config = $this->getConfig();

        $ds = $config->getDataSource($nodeId);

        if ($ds['driver'] === 'sqlite') {
            $this->logger->error('Create database query is not supported by sqlite. ths sqlite database shall have been created.');
            return true;
        }

        if (!isset($ds['dsn'])) {
            throw new Exception("Attribute 'dsn' undefined in the config.");
        }
        if (!isset($ds['database'])) {
            throw new Exception("Attribute 'database' is missing in the config.");
        }

        $dataSourceManager = DataSourceManager::getInstance();
        $conn = $dataSourceManager->connectInstance($nodeId);

        $queryDriver = $conn->getQueryDriver();

        $dbManager = new DatabaseManager($conn);
        list($ret, $sql) = $dbManager->create($ds['database'], [
            'charset' => isset($ds['charset']) ? $ds['charset'] : null,
        ]);
        if ($ret) {
            $this->logger->info("Succeed: $sql");
            $this->logger->info("Database $nodeId is created successfully.");
        } else {
            $this->logger->info("Failed: $sql");
            $this->logger->info("Failed to create database $dbName.");
        }
    }
}
