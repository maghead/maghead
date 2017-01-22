<?php

namespace Maghead\TableStatus;

use PDO;
use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;

class MySQLTableStatus
{
    protected $connection;

    protected $driver;

    public function __construct(PDO $connection, PDOMySQLDriver $driver)
    {
        $this->connection = $connection;
        $this->driver = $driver;
    }

    protected function createStatusSummaryQuery()
    {
        $query = new SelectQuery();
        $query->select([
            'CONCAT(table_schema, \'.\', table_name) AS name',
            'CONCAT(ROUND(SUM(table_rows) / 1000000, 2), \'M\') AS rows',
            'CASE WHEN SUM(data_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND(SUM(data_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN SUM(data_length) > 1024 * 1024        THEN CONCAT(ROUND(SUM(data_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(SUM(data_length) / 1024, 2), \'K\')
                                                        END AS data_size',
            'CASE WHEN SUM(index_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND(SUM(index_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN SUM(index_length) > 1024 * 1024        THEN CONCAT(ROUND(SUM(index_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(SUM(index_length) / (1024), 2), \'K\')
                                                        END AS index_size',
            'CASE WHEN SUM(data_length+index_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND(SUM(data_length+index_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN SUM(data_length+index_length) > 1024 * 1024        THEN CONCAT(ROUND(SUM(data_length+index_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(SUM(data_length+index_length) / (1024), 2), \'K\')
                                                        END AS total_size',
        ]);
        $query->from('information_schema.TABLES');
        $query->groupBy('name');

        return $query;
    }

    protected function createStatusDetailQuery()
    {
        $query = new SelectQuery();
        $query->select([
            'CONCAT(table_schema, \'.\', table_name) AS name',
            'CONCAT(ROUND(table_rows / 1000000, 2), \'M\') AS rows',

            'CASE WHEN data_length > 1024 * 1024 * 1024 THEN CONCAT(ROUND(data_length / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN data_length > 1024 * 1024        THEN CONCAT(ROUND(data_length / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(data_length / (1024), 2), \'K\')
                                                        END AS data_size',

            'CASE WHEN index_length > 1024 * 1024 * 1024 THEN CONCAT(ROUND(index_length / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN index_length > 1024 * 1024        THEN CONCAT(ROUND(index_length / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND(index_length / (1024), 2), \'K\')
                                                        END AS index_size',

            'CASE WHEN (data_length+index_length) > 1024 * 1024 * 1024 THEN CONCAT(ROUND((data_length+index_length) / (1024 * 1024 * 1024), 2), \'G\')
                  WHEN (data_length+index_length) > 1024 * 1024        THEN CONCAT(ROUND((data_length+index_length) / (1024 * 1024), 2), \'M\')
                                                        ELSE CONCAT(ROUND((data_length+index_length) / (1024), 2), \'K\')
                                                        END AS total_size',

            'ROUND(index_length / data_length, 2) AS index_frac',
        ]);
        $query->from('information_schema.TABLES');
        $query->orderBy('data_length + index_length', 'DESC');

        return $query;
    }

    public function querySummary(array $tables)
    {
        $dbName = $this->connection->query('SELECT database();')->fetchColumn();
        $query = $this->createStatusSummaryQuery();
        $query->where()->equal('table_schema', $dbName);
        if (count($tables)) {
            $query->where()
                ->in('table_name', $tables);
        }
        $args = new ArgumentArray();
        $sql = $query->toSql($this->driver, $args);
        $stm = $this->connection->prepare($sql);
        $stm->execute($args->toArray());

        return $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function queryDetails(array $tables)
    {
        $dbName = $this->connection->query('SELECT database();')->fetchColumn();
        /*
        SELECT 
            CONCAT(table_schema, '.', table_name),
            CONCAT(ROUND(table_rows / 1000000, 2), 'M') AS rows,
            CONCAT(ROUND(data_length / ( 1024 * 1024 * 1024 ), 2), 'G') AS data,
            CONCAT(ROUND(index_length / ( 1024 * 1024 * 1024 ), 2), 'G') AS idx,
            CONCAT(ROUND(( data_length + index_length ) / ( 1024 * 1024 * 1024 ), 2), 'G') AS total_size,
            ROUND(index_length / data_length, 2) AS idxfrac
            FROM information_schema.TABLES 
            WHERE table_schema = 'bossnet' ORDER  BY data_length + index_length DESC LIMIT  10;
        */
        $query = $this->createStatusDetailQuery();
        $query->where()->equal('table_schema', $dbName);
        if (count($tables)) {
            $query->where()
                ->in('table_name', $tables);
        }
        $args = new ArgumentArray();
        $sql = $query->toSql($this->driver, $args);
        $stm = $this->connection->prepare($sql);
        $stm->execute($args->toArray());

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}
