<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationRunner;
use Maghead\Migration\MigrationLoader;
use Maghead\Manager\DataSourceManager;

class MigrateStatusCommand extends MigrateBaseCommand
{
    public function brief()
    {
        return 'Show current migration status.';
    }

    public function aliases()
    {
        return array('s', 'st');
    }

    public function execute()
    {
        $dsId = $this->getCurrentDataSourceId();

        $connectionManager = DataSourceManager::getInstance();
        $conn = $connectionManager->getConnection($dsId);
        $driver = $connectionManager->getQueryDriver($dsId);

        $scripts = MigrationLoader::getDeclaredMigrationScripts();

        $runner = new MigrationRunner($conn, $driver, $this->logger, $scripts);
        $scripts = $runner->getUpgradeScripts();

        $count = count($scripts);
        $this->logger->info('Found '.$count.($count > 1 ? ' migration scripts' : ' migration script').' to be executed.');
        foreach ($scripts as $script) {
            $this->logger->info('- '.$script, 1);
        }
    }
}
