<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationRunner;

class MigrateUpgradeCommand extends MigrateBaseCommand
{
    public function brief()
    {
        return 'Run upgrade migration scripts.';
    }

    public function aliases()
    {
        return array('u', 'up');
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
        $runner->load($this->options->{'script-dir'});
        $this->logger->info("Performing upgrade over data source: $dsId...");
        $runner->runUpgrade($connection, $driver);
        $this->logger->info('Done.');
    }
}
