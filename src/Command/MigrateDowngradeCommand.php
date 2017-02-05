<?php

namespace Maghead\Command;

use Maghead\Manager\ConnectionManager;
use Maghead\Manager\MigrationManager;

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
        $connectionManager = ConnectionManager::getInstance();
        $migrationManager = new MigrationManager($connectionManager, $this->logger);
        if ($dsId = $this->getCurrentDataSourceId()) {

            /*
            if ($this->options->backup) {
                $connection = $this->getCurrentConnection();
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
            */

            $migrationManager->downgrade([$dsId], 1);
        } else {
            $migrationManager->downgrade();
        }
    }
}
