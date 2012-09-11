<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;

class MigrateCommand extends Command
{
    public function brief()
    {
        return 'migrate database schema.';
    }

    public function options($opts) 
    {
        $opts->add('new:','create new migration script.');
        $opts->add('diff:','use schema diff to generate script automatically.');
        $opts->add('status','show current migration status.');
        $opts->add('u|up','run upgrade migration scripts.');
        $opts->add('d|down','run downgrade migration scripts.');
        $opts->add('U|upgrade-diff','run upgrade from schema diff');
        $opts->add('D|data-source:','data source id.');
    }

    public function execute() 
    {
        $optNew = $this->options->new;
        $optDiff = $this->options->diff;
        $optUp = $this->options->up;
        $optUpgradeDiff = $this->options->{'upgrade-diff'};
        $optDown = $this->options->down;
        $optStatus = $this->options->status;
        $dsId = $this->options->{'data-source'} ?: 'default';

        CommandUtils::set_logger($this->logger);
        $config = CommandUtils::init_config_loader();

        if( $optNew ) {
            $generator = new MigrationGenerator('db/migrations');
            $this->logger->info( "Creating migration script for '" . $optNew . "'" );
            list($class,$path) = $generator->generate($optNew);
            $this->logger->info( "Migration script is generated: $path" );
        }
        elseif( $optDiff ) {
            $this->logger->info( "Loading schema objects..." );
            $finder = new \LazyRecord\Schema\SchemaFinder;
            $finder->paths = $config->getSchemaPaths() ?: array();
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
            $finder->paths = $config->getSchemaPaths() ?: array();
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

