<?php

namespace Maghead\TableStatus;

use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;

use Maghead\Connection;
use Maghead\Platform\MySQL\Query\TableStatusSummaryQuery;
use Maghead\Platform\MySQL\Query\TableStatusDetailQuery;
use PDO;

class MySQLTableStatus
{
    protected $conn;

    protected $driver;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
        $this->driver = $conn->getQueryDriver();
    }


    protected function getDbName()
    {
        return $this->conn->query('SELECT database()')->fetchColumn();
    }

    public function querySummary(array $tables)
    {
        $dbName = $this->getDbName();

        $query = new TableStatusSummaryQuery();

        $query->fromDatabase($dbName);
        if (count($tables)) {
            $query->fromTables($tables);
        }

        $args = new ArgumentArray();
        $sql = $query->toSql($this->driver, $args);
        $stm = $this->conn->prepare($sql);
        $stm->execute($args->toArray());

        return $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryDetails(array $tables)
    {
        $dbName = $this->getDbName();

        $query = new TableStatusDetailQuery;
        $query->fromDatabase($dbName);
        if (count($tables)) {
            $query->fromTables($tables);
        }

        $args = new ArgumentArray();
        $sql = $query->toSql($this->driver, $args);
        $stm = $this->conn->prepare($sql);
        $stm->execute($args->toArray());

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}
