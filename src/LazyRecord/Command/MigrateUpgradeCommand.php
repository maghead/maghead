<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateUpgradeCommand extends BaseCommand
{
    public function brief() { return 'Run upgrade migration scripts.'; }

    public function aliases() {
        return array('u', 'up');
    }

    public function execute() {
        $dsId = $this->getCurrentDataSourceId();
        $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
        $runner->load('db/migrations');
        $this->logger->info('Running migration scripts to upgrade...');
        $runner->runUpgrade();
        $this->logger->info('Done.');
    }

}


