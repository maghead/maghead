<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

class MigrateCommand extends Command
{

    public function options($opts) 
    {
        $opts->add('new:','create new migration script.');
        $opts->add('diff:','use schema diff to generate script automatically.');
        $opts->add('status','show current migration status.');
        $opts->add('D|data-source:','data source id.');
    }

    public function execute() 
    {
        $optNew = $this->options->new;
        $optDiff = $this->options->diff;
        $dsId = $this->options->{'data-source'} ?: 'default';

        CommandUtils::set_logger($this->logger);
        CommandUtils::init_config_loader();

        if( $optNew ) {
            $generator = new \LazyRecord\Migration\MigrationGenerator('db/migrations');
            $this->logger->info( "Creating migration script for '" . $optNew . "'" );
            list($class,$path) = $generator->generate($optNew);
            $this->logger->info( "Migration script is generated: $path" );
        }
        elseif( $optDiff ) {


        }
        else {
            // XXX: record the latest ran migration id,
            $this->logger->info('Running Migration scripts...');
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->load('db/migrations');
            $runner->runUpgrade();
            $this->logger->info('Done.');
        }

    }
}

