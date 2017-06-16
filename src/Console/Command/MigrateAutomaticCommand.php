<?php

namespace Maghead\Console\Command;

use Maghead\Migration\MigrationLoader;
use Maghead\Migration\AutomaticMigration;
use Maghead\Manager\MigrationManager;
use Maghead\Manager\DataSourceManager;

use Maghead\Schema\SchemaLoader;
use Maghead\Platform\MySQL\MySQLBackup;
use Magsql\Driver\PDOMySQLDriver;
use Exception;

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
        $args = func_get_args();
        array_shift($args);

        $schemas = $this->loadSchemasFromArguments($args);
        
        $conn = $this->dataSourceManager->getConnection($nodeId);
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

        $tableSchemas = [];
        foreach ($schemas as $schema) {
            $tableSchemas[$schema->getTable()] = $schema;
        }

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

            if ($pe = $e->getPrevious()) {
                if ($pe->xdebug_message) {
                    $this->logger->warn($pe->xdebug_message);
                } else if (isset($pe->errorInfo)) {
                    $this->logger->warn(join(' ',$pe->errorInfo));
                } else if ($msg = $pe->getMessage()) {
                    $this->logger->warn($msg);
                }
            }

            $this->logger->warn('Rolling back ...');
            $conn->rollback();
            $this->logger->warn('Recovered, escaping...');

        }
        $this->logger->info('Done.');
    }
}
