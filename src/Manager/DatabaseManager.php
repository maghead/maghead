<?php

namespace Maghead\Manager;

use Maghead\Connection;
use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use SQLBuilder\Universal\Query\DropDatabaseQuery;

class DatabaseManager
{
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }


    public function create(string $nodeId, string $dbname)
    {
        $nodeConfig = $this->connectionManager->getNodeConfig($nodeId);
        $dsn = DSNParser::parse($nodeConfig['dsn']);
        if ($nodeConfig['driver'] === "sqlite") {
            // we simply create a new PDO connection with the given dbname.
            return $this->createSqliteDatabase($dsn, $nodeConfig, $dbname);
        }
        return $this->createServerDatabase($nodeId, $dbname);
    }

    public function drop(string $nodeId, string $dbname)
    {
        $nodeConfig = $this->connectionManager->getNodeConfig($nodeId);
        $dsn = DSNParser::parse($nodeConfig['dsn']);
        if ($nodeConfig['driver'] === "sqlite") {
            if (file_exists("{$dbname}.sqlite")) {
                unlink("{$dbname}.sqlite");
            }
            return;
        }
        return $this->dropServerDatabase($nodeId, $dbname);
    }

    protected function dropServerDatabase($nodeId, string $dbname)
    {
        $conn = $this->connectionManager->connectInstance($nodeId);
        $q = new DropDatabaseQuery($dbname);
        $queryDriver = PDODriverFactory::create($conn);
        $sql = $q->toSql($queryDriver, new ArgumentArray());
        $conn->query($sql);
    }

    protected function createServerDatabase($nodeId, string $dbname)
    {
        $nodeConfig = $this->connectionManager->getNodeConfig($nodeId);
        $conn = $this->connectionManager->connectInstance($nodeId);

        // create new data source config
        $dsn = DSNParser::parse($nodeConfig['dsn']);
        $dsn->setAttribute('dbname', $dbname);
        $nodeConfig['dsn'] = $dsn->__toString();

        $q = new CreateDatabaseQuery($dbname);
        $q->ifNotExists();
        if (isset($nodeConfig['charset'])) {
            $q->characterSet($nodeConfig['charset']);
        } else {
            $q->characterSet('utf8');
        }

        $queryDriver = PDODriverFactory::create($conn);
        $sql = $q->toSql($queryDriver, new ArgumentArray());
        $conn->query($sql);
        return [$conn, $nodeConfig];
    }

    protected function createSqliteDatabase(DSN $dsn, array $nodeConfig, string $dbname)
    {
        $nodeConfig['dsn'] = "sqlite:{$dbname}.sqlite";
        return [Connection::connect($nodeConfig), $nodeConfig];
    }
}
