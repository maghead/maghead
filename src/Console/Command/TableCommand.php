<?php

namespace Maghead\Console\Command;

use Maghead\TableStatus\MySQLTableStatus;
use Magsql\Driver\PDOMySQLDriver;
use CLIFramework\Component\Table\Table;

class TableCommand extends BaseCommand
{
    public function brief()
    {
        return 'show table status.';
    }

    public function aliases()
    {
        return ['tables', 't'];
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('v|verbose', 'Display verbose information');
    }

    public function execute($nodeId = 'master')
    {
        $tables = func_get_args();
        array_shift($tables);

        $config = $this->getConfig(true);

        $conn = $this->dataSourceManager->getConnection($nodeId);
        $driver = $conn->getQueryDriver();

        if (!$driver instanceof PDOMySQLDriver) {
            $driverClass = get_class($driver);
            $this->logger->error("Driver {$driverClass} not supported.");
        }

        $status = new MySQLTableStatus($conn);

        $this->logger->info('Table Status:');
        $rows = $status->queryDetails($tables);
        $this->displayRows($rows);

        $this->logger->newline();
        $this->logger->info('Table Status Summary:');
        $rows = $status->querySummary($tables);
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
