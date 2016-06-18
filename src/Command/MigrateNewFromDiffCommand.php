<?php

namespace LazyRecord\Command;

use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\SchemaUtils;
use LazyRecord\Console;

class MigrateNewFromDiffCommand extends BaseCommand
{
    public function aliases()
    {
        return array('nd');
    }

    public function execute($taskName)
    {
        $dsId = $this->getCurrentDataSourceId();

        $this->logger->info('Loading schema objects...');
        $finder = new SchemaFinder();
        $finder->setPaths($this->config->getSchemaPaths() ?: array());
        $finder->find();
        $schemas = SchemaUtils::findSchemasByArguments($this->getConfigLoader(), [], $this->logger);
        $this->logger->info('Found '.count($schemas) == 0 .' schemas');

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'db/migrations');
        $this->logger->info('Creating migration script from diff');
        list($class, $path) = $generator->generateWithDiff($taskName, $dsId, $schemas);
        $this->logger->info("Migration script is generated: $path");
    }
}
