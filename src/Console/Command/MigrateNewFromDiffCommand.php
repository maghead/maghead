<?php

namespace Maghead\Console\Command;

use Maghead\Migration\MigrationGenerator;
use Maghead\Schema\SchemaLoader;

class MigrateNewFromDiffCommand extends MigrateBaseCommand
{
    public function aliases()
    {
        return array('nd');
    }

    public function execute($nodeId = 'master', $taskName)
    {
        $conn = $this->dataSourceManager->getConnection($nodeId);
        $driver = $conn->getQueryDriver();

        $config = $this->getConfig();

        $schemas = $this->loadSchemasFromArguments([]);

        $generator = new MigrationGenerator($this->logger, $this->options->{'script-dir'});
        $this->logger->debug('Creating migration script from diff');

        $tableSchemas = [];
        foreach ($schemas as $schema) {
            $tableSchemas[ $schema->getTable() ] = $schema;
        }

        list($class, $path) = $generator->generateWithDiff($taskName, $nodeId, $schemaMap);
        $this->logger->info("Migration script is generated: $path");
    }
}
