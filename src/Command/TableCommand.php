<?php

namespace Maghead\Command;

use Maghead\TableStatus\MySQLTableStatus;
use SQLBuilder\Driver\PDOMySQLDriver;
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

    public function execute()
    {
        $tables = func_get_args();
        $configLoader = $this->getConfigLoader(true);

        $dataSource = $this->getCurrentDataSourceId();
        $conn = $this->getCurrentConnection();
        $driver = $this->getCurrentQueryDriver();

        if ($driver instanceof PDOMySQLDriver) {
            $status = new MySQLTableStatus($conn, $driver);

            $this->logger->info('Table Status:');
            $rows = $status->queryDetails($tables);
            $this->displayRows($rows);

            $this->logger->newline();
            $this->logger->info('Table Status Summary:');
            $rows = $status->querySummary($tables);
            $this->displayRows($rows);
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
