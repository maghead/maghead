<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationGenerator;
use Maghead\Schema\SchemaFinder;
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
        $this->logger->debug('Loading schema objects...');
        $finder = new SchemaFinder();
        $finder->setPaths($config->getSchemaPaths() ?: array());
        $finder->load();

        $generator = new MigrationGenerator($this->logger, 'db/migrations');
        $this->logger->debug('Creating migration script from diff');

        $schemaMap = SchemaLoader::loadSchemaTableMap();
        list($class, $path) = $generator->generateWithDiff($taskName, $nodeId, $schemaMap);
        $this->logger->info("Migration script is generated: $path");
    }
}
