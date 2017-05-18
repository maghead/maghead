<?php

namespace Maghead\Console\Command;

use Magsql\Driver\PDOMySQLDriver;
use Magsql\Universal\Query\SelectQuery;
use Magsql\ArgumentArray;
use CLIFramework\Component\Table\Table;

use Maghead\Manager\DataSourceManager;
use Maghead\Platform\MySQL\Query\IndexStatsQuery;
use PDO;

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

    public function options($opts)
    {
        $opts->add('t|table+');
    }

    public function execute($nodeId = 'master')
    {
        $conn = $this->dataSourceManager->getConnection($nodeId);
        $driver = $conn->getQueryDriver();

        if (!$driver instanceof PDOMySQLDriver) {
            $driverClass = get_class($driver);
            $this->logger->error("Driver '$driverClass' is not supported.");
        }

        $dbName = $conn->query('SELECT database();')->fetchColumn();

        $query = new IndexStatsQuery;
        $query->fromDatabase($dbName);

        $tables = $this->options->table;
        if (!empty($tables)) {
            $query->fromTables($tables);
        }

        $args = new ArgumentArray();
        $sql = $query->toSql($driver, $args);

        $this->logger->debug($sql);

        $stm = $conn->prepare($sql);
        $stm->execute($args->toArray());
        $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
        $this->displayRows($rows);
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
