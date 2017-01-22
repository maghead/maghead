<?php

namespace Maghead\Command;

use SQLBuilder\Driver\PDOMySQLDriver;
use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\ArgumentArray;
use CLIFramework\Component\Table\Table;

class IndexCommand extends BaseCommand
{
    public function brief()
    {
        return 'show indexes.';
    }

    public function aliases()
    {
        return ['idx'];
    }

    public function execute()
    {
        $tables = func_get_args();
        $dataSource = $this->getCurrentDataSourceId();
        $conn = $this->getCurrentConnection();
        $driver = $this->getCurrentQueryDriver();

        if ($driver instanceof PDOMySQLDriver) {
            $dbName = $conn->query('SELECT database();')->fetchColumn();

            $query = new SelectQuery();
            $query->select([
                'stat.TABLE_NAME',
                'CONCAT(stat.INDEX_NAME, " (", GROUP_CONCAT(DISTINCT stat.COLUMN_NAME ORDER BY stat.SEQ_IN_INDEX ASC), ")")' => 'COLUMNS',
                'stat.INDEX_TYPE',
                'stat.NULLABLE',
                'stat.NON_UNIQUE',
                'stat.COMMENT',
                'SUM(index_stat.stat_value)' => 'pages',
                'CONCAT(ROUND((SUM(stat_value) * @@innodb_page_size) / 1024 / 1024, 1), "MB")' => 'page_size',
            ]);
            $query->from('information_schema.STATISTICS stat');

            $query->join('mysql.innodb_index_stats', 'index_stat', 'LEFT')
                ->on('index_stat.database_name = stat.TABLE_SCHEMA 
                    AND index_stat.table_name = stat.TABLE_NAME 
                    AND index_stat.index_name = stat.INDEX_NAME')
                ;

            $query->where()
                ->equal('stat.TABLE_SCHEMA', 'bossnet')
                ;

            if (!empty($tables)) {
                $query->where()
                    ->in('stat.TABLE_NAME', $tables)
                    ;
            }

            $query->groupBy('stat.INDEX_NAME');
            $query->groupBy('stat.TABLE_NAME');
            $query->groupBy('stat.TABLE_SCHEMA');

            $query->orderBy('stat.TABLE_SCHEMA', 'ASC');
            $query->orderBy('stat.TABLE_NAME', 'ASC');
            $query->orderBy('stat.INDEX_NAME', 'ASC');
            $query->orderBy('stat.SEQ_IN_INDEX', 'ASC');

            $args = new ArgumentArray();
            $sql = $query->toSql($driver, $args);

            $this->logger->debug($sql);

            $stm = $conn->prepare($sql);
            $stm->execute($args->toArray());
            $rows = $stm->fetchAll();
            $this->displayRows($rows);

            /*
            $status = new MySQLTableStatus($conn, $driver);
            $this->logger->info("Table Status:");
            $rows = $status->queryDetails($tables);
            $this->displayRows($rows);
            $this->logger->newline();
            $this->logger->info("Table Status Summary:");
            $rows = $status->querySummary($tables);
             */
        } else {
            $this->logger->error('Driver not supported.');
        }
    }

    protected function displayRows(array $rows)
    {
        if (count($rows)) {
            $table = new Table();
            $headers = array_keys($rows[0]);
            $table->setHeaders($headers);
            foreach ($rows as $row) {
                $table->addRow(array_values($row));
            }
            echo $table->render();
        }
    }
}
