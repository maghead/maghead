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

/**
 * Purpose:
 *
 * 1. Create Database (no schema involved)
 * 2. Drop Database (no schema involved)
 *
 * We don't have to create sqlite db, it will be automatically created by PDO,
 * so we don't have to handle sqlite.
 *
 * Server-based database like mysql, pgsql will be handled.
 *
 * Things like creating new node config should be handled in DataSourceManager.
 */
class DatabaseManager
{
    protected $connection;

    protected $queryDriver;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->queryDriver = $connection->getQueryDriver();
    }

    /**
     * force create will drop the existing db and create a new database.
     */
    public function forceCreate($dbname, array $nodeConfig = [])
    {
        $this->drop($dbname);
        $this->create($dbname);
    }

    public function create($dbname, array $nodeConfig = [])
    {
        $q = new CreateDatabaseQuery($dbname);
        $q->ifNotExists();
        if (isset($nodeConfig['charset'])) {
            $q->characterSet($nodeConfig['charset']);
        } else {
            $q->characterSet('utf8');
        }
        $sql = $q->toSql($this->queryDriver, new ArgumentArray());
        $this->connection->query($sql);
    }

    public function drop(string $dbname)
    {
        $q = new DropDatabaseQuery($dbname);
        $q->ifExists();
        $sql = $q->toSql($this->queryDriver, new ArgumentArray());
        $this->connection->query($sql);
    }
}
