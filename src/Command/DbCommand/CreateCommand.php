<?php

namespace Maghead\Command\DbCommand;

use Maghead\Command\BaseCommand;
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

    public function execute()
    {
        $configLoader = $this->getConfigLoader(true);
        $dsId = $this->getCurrentDataSourceId();
        $ds = $configLoader->getDataSource($dsId);

        if (!isset($ds['dsn'])) {
            throw new Exception("Attribute 'dsn' undefined in data source settings.");
        }

        $dsnParser = new DSNParser();
        $dsn = $dsnParser->parse($ds['dsn']);

        $dbName = $dsn->getAttribute('dbname');

        $dsn->removeAttribute('dbname');

        $this->logger->debug('Connection DSN: '.$dsn);

        $pdo = new PDO($dsn, @$ds['user'], @$ds['pass'], @$ds['connection_options']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $queryDriver = PDODriverFactory::create($pdo);

        if ($queryDriver instanceof PDOSQLiteDriver) {
            $this->logger->info('Create database query is not supported by sqlite. ths sqlite database shall have been created.');

            return true;
        }

        $q = new CreateDatabaseQuery($dbName);
        $q->ifNotExists();
        if (isset($ds['charset'])) {
            $q->characterSet($ds['charset']);
        } else {
            $q->characterSet('utf8');
        }

        $sql = $q->toSql($queryDriver, new ArgumentArray());
        $this->logger->info($sql);

        if ($pdo->query($sql) === false) {
            list($statusCode, $errorCode, $message) = $pdo->errorInfo();
            $this->logger->error("$statusCode:$errorCode $message");

            return false;
        }
        $this->logger->info('Database created successfully.');
    }
}
