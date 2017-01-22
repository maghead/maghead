<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationRunner;

class MigrateDowngradeCommand extends MigrateBaseCommand
{
    public function brief()
    {
        return 'Run downgrade migration scripts.';
    }

    public function aliases()
    {
        return array('d', 'down');
    }

    public function execute()
    {
        $connection = $this->getCurrentConnection();
        $driver = $this->getCurrentQueryDriver();
        if ($this->options->backup) {
            if (!$driver instanceof PDOMySQLDriver) {
                $this->logger->error('backup is only supported for MySQL');

                return false;
            }
            $this->logger->info('Backing up database...');
            $backup = new MySQLBackup();
            if ($dbname = $backup->incrementalBackup($connection)) {
                $this->logger->info("Backup at $dbname");
            }
        }

        $dsId = $this->getCurrentDataSourceId();
        $runner = new MigrationRunner($this->logger, $dsId);
        $runner->load($this->options->{'script-dir'} ?: 'db/migrations');
        $this->logger->info("Performing downgrade over data source: $dsId...");
        $runner->runDowngrade($connection, $driver);
        $this->logger->info('Done.');
    }
}
