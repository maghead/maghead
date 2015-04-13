<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaFinder;

class MigrateAutomaticCommand extends BaseCommand
{
    public function brief() { return 'Run upgrade automatically.'; }

    public function aliases() {
        return array('au', 'auto');
    }

    public function options($opts) 
    {
        parent::options($opts);
    }

    public function execute() {
        $dsId = $this->getCurrentDataSourceId();
        $this->logger->info( "Loading schema objects..." );
        $finder = new SchemaFinder;
        $finder->paths = $this->config->getSchemaPaths() ?: array();
        $finder->find();
        $schemas = $finder->getSchemas();
        $runner = new MigrationRunner($dsId);
        $runner->runUpgradeAutomatically($schemas);
        $this->logger->info('Done.');
    }

}


