<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateDowngradeCommand extends BaseCommand
{
    public function brief() {
        return 'Run downgrade migration scripts.';
    }

    public function aliases() {
        return array('d', 'down');
    }

    public function execute()
    {
        $dsId = $this->getCurrentDataSourceId();
        $runner = new MigrationRunner($dsId);
        $runner->load('db/migrations');
        $this->logger->info('Running migration scripts to downgrade...');
        $runner->runDowngrade();
        $this->logger->info('Done.');
    }

}


