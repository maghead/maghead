<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationLoader;
use Maghead\Migration\AutomaticMigration;
use Maghead\Manager\MigrationManager;
use Maghead\Manager\DataSourceManager;

use Maghead\Schema\SchemaLoader;
use Maghead\Backup\MySQLBackup;
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

    public function execute($nodeId = "master")
    {
        $dataSourceManager = DataSourceManager::getInstance();
        $conn = $dataSourceManager->getConnection($nodeId);
        $driver = $conn->getQueryDriver();

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

        // TODO: this could be refactored with MigrationManager
        $this->logger->info("Performing automatic upgrade over data source: $nodeId");

        $tableSchemas = SchemaLoader::loadSchemaTableMap();
        $script = new AutomaticMigration($conn, $driver, $this->logger, $this->options);
        try {
            $this->logger->info('Begining transaction...');
            $conn->beginTransaction();

            // where to find the schema?
            $script->upgrade($tableSchemas);

            $this->logger->info('Committing...');
            $conn->commit();
        } catch (Exception $e) {
            $this->logger->error('Exception was thrown: '.$e->getMessage());
            $this->logger->warn('Rolling back ...');
            $conn->rollback();
            $this->logger->warn('Recovered, escaping...');
            throw $e;
        }
        $this->logger->info('Done.');
    }
}
