<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateStatusCommand extends BaseCommand
{

    public function brief() {  return 'Show current migration status.'; }

    public function aliases() {
        return array('s', 'st');
    }


    public function execute() {
        $dsId = $this->getCurrentDataSourceId();

        $runner = new MigrationRunner($dsId);
        $runner->load('db/migrations');
        $scripts = $runner->getUpgradeScripts($dsId);
        $count = count($scripts);
        $this->logger->info("Found " . $count . ($count > 1 ? ' migration scripts' : ' migration script') . ' to be executed.');
        foreach( $scripts as $script ) {
            $this->logger->info( '- ' . $script , 1 );
        }
    }

}


