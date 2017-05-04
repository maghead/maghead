<?php

namespace Maghead\Command;

use Maghead\Manager\DataSourceManager;
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

    public function execute($nodeId, $steps = 1)
    {
        $dataSourceManager = DataSourceManager::getInstance();
        $migrationManager = new MigrationManager($dataSourceManager, $this->logger);
        $migrationManager->downgrade([$nodeId], $steps);
    }
}
