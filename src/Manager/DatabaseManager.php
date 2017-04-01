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
        $ds = $this->connectionManager->getNodeConfig($nodeId);
        $dsn = DSNParser::parse($ds['dsn']);
        switch ($ds['driver']) {
        case 'sqlite':
            // we simply create a new PDO connection with the given dbname.
            return $this->createSqliteDatabase($dsn, $ds, $dbname);
        case 'pgsql':
        case 'mysql':
            return $this->createServerDatabase($dsn, $ds, $dbname);
        }
    }

    public function drop(string $nodeId, string $dbname)
    {
        $ds = $this->connectionManager->getNodeConfig($nodeId);
        $dsn = DSNParser::parse($ds['dsn']);
        switch ($ds['driver']) {
        case 'sqlite':
            if (file_exists("{$dbname}.sqlite")) {
                unlink("{$dbname}.sqlite");
            }
            return;
        case 'pgsql':
        case 'mysql':
            return $this->dropServerDatabase($nodeId, $dbname);
        }

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
        $conn = $this->connectionManager->connectInstance($nodeId);
        $q = new CreateDatabaseQuery($dbname);
        $q->ifNotExists();
        if (isset($ds['charset'])) {
            $q->characterSet($ds['charset']);
        } else {
            $q->characterSet('utf8');
        }

        $queryDriver = PDODriverFactory::create($conn);
        $sql = $q->toSql($queryDriver, new ArgumentArray());
        $conn->query($sql);
        return [$conn, $ds];
    }

    protected function createSqliteDatabase(DSN $dsn, array $ds, string $dbname)
    {
        $ds['dsn'] = "sqlite:{$dbname}.sqlite";
        return [Connection::connect($ds), $ds];
    }
}
