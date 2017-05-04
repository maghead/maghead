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

    public function execute($nodeId = 'master')
    {
        $migrationManager = new MigrationManager($this->dataSourceManager, $this->logger);
        $migrationManager->upgrade([$nodeId], 1);
    }
}
