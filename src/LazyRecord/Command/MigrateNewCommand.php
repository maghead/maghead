<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateNewCommand extends BaseCommand
{
    public function aliases() {
        return array('n', 'new');
    }

    public function execute() {
        $dsId = $this->getCurrentDataSourceId();

        $generator = new MigrationGenerator('db/migrations');
        $this->logger->info( "Creating migration script for '" . $optNew . "'" );
        list($class,$path) = $generator->generate($optNew);
        $this->logger->info( "Migration script is generated: $path" );
    }
}


