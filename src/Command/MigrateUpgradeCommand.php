<?php

namespace Maghead\Command;

use Maghead\Manager\DataSourceManager;
use Maghead\Manager\MigrationManager;

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
        $connectionManager = DataSourceManager::getInstance();
        $migrationManager = new MigrationManager($connectionManager, $this->logger);
        if ($dsId = $this->getCurrentDataSourceId()) {

            /*
            $conn = $this->getCurrentConnection();
            $driver = $this->getCurrentQueryDriver();
            if ($this->options->backup) {
                if (!$driver instanceof PDOMySQLDriver) {
                    $this->logger->error('backup is only supported for MySQL');

                    return false;
                }
                $this->logger->info('Backing up database...');
                $backup = new MySQLBackup();
                if ($dbname = $backup->incrementalBackup($conn)) {
                    $this->logger->info("Backup at $dbname");
                }
            }
            */

            $migrationManager->upgrade([$dsId], 1);
        } else {
            $migrationManager->upgrade();
        }
    }
}
