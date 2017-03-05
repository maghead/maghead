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

    public function drop($nodeId, $dbname)
    {
        $ds = $this->connectionManager->getDataSource($nodeId);
        $dsn = DSNParser::parse($ds['dsn']);
        switch ($ds['driver']) {
        case 'sqlite':
            if (file_exists($dbname)) {
                unlink($dbname);
            }
            return;
        case 'pgsql':
        case 'mysql':
            return $this->dropServerDatabase($dsn, $ds, $dbname);
        }

    }

    public function create(string $nodeId, string $dbname)
    {
        $ds = $this->connectionManager->getDataSource($nodeId);
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

    protected function dropServerDatabase(DSN $dsn, array $ds, string $dbname)
    {
        $dsn->removeAttribute('dbname');
        $ds['dsn'] = $dsn->__toString();
        $conn = Connection::connect($ds);
        $q = new DropDatabaseQuery($dbname);
        $queryDriver = PDODriverFactory::create($conn);
        $sql = $q->toSql($queryDriver, new ArgumentArray());
        var_dump($sql);
        $conn->query($sql);
    }

    protected function createServerDatabase(DSN $dsn, array $ds, string $dbname)
    {
        $dsn->removeAttribute('dbname');
        $ds['dsn'] = $dsn->__toString();
        $conn = Connection::connect($ds);


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
        $ds['dsn'] = "sqlite:$dbname";
        return [Connection::connect($ds), $ds];
    }
}
