<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateCommand extends BaseCommand
{
    public function brief()
    {
        return 'Migrate database schema.';
    }

    public function init() {
        $this->command('upgrade', 'LazyRecord\\Command\\MigrateUpgradeCommand');
        $this->command('downgrade', 'LazyRecord\\Command\\MigrateDowngradeCommand');
        $this->command('new', 'LazyRecord\\Command\\MigrateNewCommand');
    }

    public function options($opts) 
    {
        parent::options($opts);

        $opts->add('new:','create new migration script.');

        $opts->add('diff:','use schema diff to generate script automatically.');

        $opts->add('status','show current migration status.');

        $opts->add('u|up','run upgrade migration scripts.');
        $opts->add('d|down','run downgrade migration scripts.');

        // force upgrade from diff
        $opts->add('U|upgrade-diff','run upgrade from schema diff');
    }

    public function execute() 
    {
        return parent::execute();
        $optNew = $this->options->new;
        $optDiff = $this->options->diff;
        $optUp = $this->options->up;
        $optUpgradeDiff = $this->options->{'upgrade-diff'};
        $optDown = $this->options->down;
        $optStatus = $this->options->status;
        $dsId = $this->getCurrentDataSourceId();

        if ($optNew) {
            $generator = new MigrationGenerator('db/migrations');
            $this->logger->info( "Creating migration script for '" . $optNew . "'" );
            list($class,$path) = $generator->generate($optNew);
            $this->logger->info( "Migration script is generated: $path" );
        }
        elseif( $optDiff ) {
            $this->logger->info( "Loading schema objects..." );
            $finder = new \LazyRecord\Schema\SchemaFinder;
            $finder->paths = $this->config->getSchemaPaths() ?: array();
            $finder->find();
            $schemas = $finder->getSchemas();

            $generator = new MigrationGenerator('db/migrations');
            $this->logger->info( "Creating migration script from diff" );
            list($class,$path) = $generator->generateWithDiff( $optDiff ,$dsId,$schemas);
            $this->logger->info( "Migration script is generated: $path" );
        }
        elseif( $optUpgradeDiff ) {
            $this->logger->info( "Loading schema objects..." );
            $finder = new \LazyRecord\Schema\SchemaFinder;
            $finder->paths = $this->config->getSchemaPaths() ?: array();
            $finder->find();
            $schemas = $finder->getSchemas();
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->runUpgradeFromSchemaDiff($schemas);
        }
        elseif( $optStatus ) {
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->load('db/migrations');
            $scripts = $runner->getUpgradeScripts($dsId);
            $count = count($scripts);
            $this->logger->info("Found " . $count . ($count > 1 ? ' migration scripts' : ' migration script') . ' to be executed.');
            foreach( $scripts as $script ) {
                $this->logger->info( '- ' . $script , 1 );
            }
        }
        elseif( $optUp ) {
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->load('db/migrations');
            $this->logger->info('Running migration scripts to upgrade...');
            $runner->runUpgrade();
            $this->logger->info('Done.');
        }
        elseif( $optDown ) {
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->load('db/migrations');
            $this->logger->info('Running migration scripts to downgrade...');
            $runner->runDowngrade();
            $this->logger->info('Done.');
        }
    }
}

