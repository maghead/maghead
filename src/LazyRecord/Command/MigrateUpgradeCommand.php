<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Schema\SchemaFinder;

class MigrateUpgradeCommand extends BaseCommand
{
    public function brief() { return 'Run upgrade migration scripts.'; }

    public function aliases() {
        return array('u', 'up');
    }

    public function options($opts) 
    {
        parent::options($opts);
        $opts->add('U|upgrade-diff','Run upgrade from schema diff');
    }

    public function execute() {
        $dsId = $this->getCurrentDataSourceId();
        if ($this->options->{'upgrade-diff'}) {
            $this->logger->info( "Loading schema objects..." );
            $finder = new SchemaFinder;
            $finder->paths = $this->config->getSchemaPaths() ?: array();
            $finder->find();
            $schemas = $finder->getSchemas();
            $runner = new MigrationRunner($dsId);
            $runner->runUpgradeAutomatically($schemas);
        } else {
            $runner = new MigrationRunner($dsId);
            $runner->load('db/migrations');
            $this->logger->info('Running migration scripts to upgrade...');
            $runner->runUpgrade();
        }
        $this->logger->info('Done.');
    }

}


