<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;
use LazyRecord\Console;

class MigrateNewCommand extends BaseCommand
{
    public function aliases() {
        return array('n', 'new');
    }

    public function execute($taskName) {
        $dsId = $this->getCurrentDataSourceId();

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'db/migrations');
        $this->logger->info( "Creating migration script for '" . $taskName . "'" );
        list($class, $path) = $generator->generate($taskName);
        $this->logger->info( "Migration script is generated: $path" );
    }
}


