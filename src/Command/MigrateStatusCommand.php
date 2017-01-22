<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationRunner;
use Maghead\ConnectionManager;

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

        $connectionManager = ConnectionManager::getInstance();
        $conn = $connectionManager->getConnection($dsId);
        $driver = $connectionManager->getQueryDriver($dsId);

        $runner = new MigrationRunner($this->logger, $dsId);
        $runner->load($this->options->{'script-dir'} ?: 'db/migrations');
        $scripts = $runner->getUpgradeScripts($conn, $driver);
        $count = count($scripts);
        $this->logger->info('Found '.$count.($count > 1 ? ' migration scripts' : ' migration script').' to be executed.');
        foreach ($scripts as $script) {
            $this->logger->info('- '.$script, 1);
        }
    }
}
