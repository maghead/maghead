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

    public function execute($nodeId)
    {
        $dataSourceManager = DataSourceManager::getInstance();
        $migrationManager = new MigrationManager($dataSourceManager, $this->logger);
        $migrationManager->upgrade([$nodeId], 1);
    }
}
