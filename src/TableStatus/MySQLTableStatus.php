<?php

namespace Maghead\TableStatus;

use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;

use Maghead\Connection;
use Maghead\Platform\MySQL\TableStatusSummaryQuery;
use Maghead\Platform\MySQL\TableStatusDetailQuery;

class MySQLTableStatus
{
    protected $conn;

    protected $driver;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
        $this->driver = $conn->getQueryDriver();
    }

    public function querySummary(array $tables)
    {
        $dbName = $this->conn->query('SELECT database();')->fetchColumn();
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
        $dbName = $this->conn->query('SELECT database();')->fetchColumn();
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
