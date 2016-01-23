<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;
use LazyRecord\ConnectionManager;
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

            $query = new SelectQuery;
            $query->select([
                'TABLE_NAME',
                'CONCAT(INDEX_NAME, " (", GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX ASC), ")")' => 'COLUMNS', 
                'INDEX_TYPE',
                'NULLABLE',
                'NON_UNIQUE',
                'COMMENT ',
            ]);
            $query->from('information_schema.STATISTICS');
            $query->where()
                ->equal('TABLE_SCHEMA', 'bossnet')
                ;

            if (!empty($tables)) {
                $query->where()
                    ->in('TABLE_NAME', $tables)
                    ;
            }

            $query->groupBy('INDEX_NAME');
            $query->groupBy('TABLE_NAME');
            $query->groupBy('TABLE_SCHEMA');

            $query->orderBy('TABLE_SCHEMA', 'ASC');
            $query->orderBy('TABLE_NAME', 'ASC');
            $query->orderBy('INDEX_NAME', 'ASC');
            $query->orderBy('SEQ_IN_INDEX', 'ASC');

            $args = new ArgumentArray;
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
            $this->logger->error("Driver not supported.");
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
