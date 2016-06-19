<?php

namespace LazyRecord\Command;

use LazyRecord\Migration\MigrationRunner;
use LazyRecord\Migration\AutomaticMigration;
use LazyRecord\Schema\SchemaLoader;

use LazyRecord\ServiceContainer;
use LazyRecord\Backup\MySQLBackup;
use SQLBuilder\Driver\PDOMySQLDriver;

class MigrateAutomaticCommand extends MigrateBaseCommand
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
        AutomaticMigration::options($opts);
    }

    public function execute()
    {
        $dsId = $this->getCurrentDataSourceId();
        $container = ServiceContainer::getInstance();

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

        $runner = new MigrationRunner($this->logger, $dsId);
        $this->logger->info("Performing automatic upgrade over data source: $dsId");

        $tableSchemas = SchemaLoader::loadSchemaTableMap($this->getConfigLoader());
        $runner->runUpgradeAutomatically($conn, $driver, $tableSchemas, $this->options);
        $this->logger->info('Done.');
    }
}
