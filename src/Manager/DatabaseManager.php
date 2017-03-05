<?php

namespace Maghead\Manager;

use Maghead\Connection;
use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;

use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;

class DatabaseManager
{
    protected $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function create($nodeId, $dbname)
    {
        $ds = $this->connectionManager->getDataSource($nodeId);
        $dsn = DSNParser::parse($ds['dsn']);
        switch ($ds['driver']) {
        case 'sqlite':
            // we simply create a new PDO connection with the given dbname.
            return $this->createSqliteDatabase($dsn, $ds, $dbname);
        case 'pgsql':
        case 'mysql':
            return $this->createMysqlDatabase($dsn, $ds, $dbname);
        }
    }

    protected function createMysqlDatabase(DSN $dsn, $ds, $dbname)
    {
        $dbName = $dsn->getAttribute('dbname');
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

    protected function createSqliteDatabase(DSN $dsn, $ds, $dbname)
    {
        $ds['dsn'] = "sqlite:$dbname";
        return [Connection::connect($ds), $ds];
    }


}



