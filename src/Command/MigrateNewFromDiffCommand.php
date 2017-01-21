<?php

namespace LazyRecord\Command;

use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\SchemaLoader;

class MigrateNewFromDiffCommand extends MigrateBaseCommand
{
    public function aliases()
    {
        return array('nd');
    }

    public function execute($taskName)
    {
        $dsId = $this->getCurrentDataSourceId();
        $config = $this->getConfigLoader(true);

        $this->logger->info('Loading schema objects...');
        $finder = new SchemaFinder();
        $finder->setPaths($config->getSchemaPaths() ?: array());
        $finder->load();

        $generator = new MigrationGenerator($this->logger, 'db/migrations');
        $this->logger->info('Creating migration script from diff');

        $schemaMap = SchemaLoader::loadSchemaTableMap();
        list($class, $path) = $generator->generateWithDiff($taskName, $dsId, $schemaMap);
        $this->logger->info("Migration script is generated: $path");
    }
}
