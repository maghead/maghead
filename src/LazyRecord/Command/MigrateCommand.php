<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

class MigrateCommand extends Command
{

    public function options($opts) 
    {
        $opts->add('new:');
        $opts->add('diff:');
    }

    public function execute() 
    {
        $optNew = $this->options->new;
        $optDiff = $this->options->diff;

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
            $this->logger->info('Running Migration scripts...');
            $runner = new \LazyRecord\Migration\MigrationRunner('default');
            $runner->load('db/migrations');
            $runner->runUpgrade();
            $this->logger->info('Done.');
        }

    }
}

