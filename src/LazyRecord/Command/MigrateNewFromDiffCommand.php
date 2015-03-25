<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Migration\MigrationRunner;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Command\BaseCommand;

class MigrateNewFromDiffCommand extends BaseCommand
{
    public function aliases() {
        return array('nd');
    }

    public function execute($taskName) {
        $dsId = $this->getCurrentDataSourceId();

        $this->logger->info( "Loading schema objects..." );
        $finder = new SchemaFinder;
        $finder->paths = $this->config->getSchemaPaths() ?: array();
        $finder->find();
        $schemas = $finder->getSchemas();

        $this->logger->info('Found ' . count($schemas) == 0 . ' schemas');

        $generator = new MigrationGenerator('db/migrations');
        $this->logger->info( "Creating migration script from diff" );
        list($class,$path) = $generator->generateWithDiff($taskName, $dsId, $schemas);
        $this->logger->info( "Migration script is generated: $path" );
    }
}


