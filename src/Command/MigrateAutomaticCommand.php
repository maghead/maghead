<?php

namespace LazyRecord\Command;

use LazyRecord\Migration\MigrationRunner;

use LazyRecord\ServiceContainer;
use LazyRecord\Backup\MySQLBackup;
use SQLBuilder\Driver\PDOMySQLDriver;

class MigrateAutomaticCommand extends BaseCommand
{
    public function brief()
    {
        return 'Run upgrade automatically.';
    }

    public function aliases()
    {
        return array('au', 'auto');
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('no-drop-column', 'Do not drop column in automatic migration process.');
        $opts->add('b|backup', 'Backup database before running migration script.');
    }

    public function execute()
    {
        $dsId = $this->getCurrentDataSourceId();
        $container = ServiceContainer::getInstance();

        if ($this->options->backup) {
            $connection = $this->getCurrentConnection();
            $driver = $this->getCurrentQueryDriver();
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

        $runner = new MigrationRunner($dsId);
        $runner->runUpgradeAutomatically($this->options);
        $this->logger->info('Done.');
    }
}
