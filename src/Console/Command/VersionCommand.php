<?php

namespace Maghead\Console\Command;

use Maghead\Manager\MetadataManager;

class VersionCommand extends BaseCommand
{
    public function brief()
    {
        return 'show database version';
    }

    public function usage()
    {
        return "maghead version\n";
    }

    public function execute($nodeId = 'master')
    {
        $conn = $this->dataSourceManager->getWriteConnection($nodeId);
        $meta = new MetadataManager($conn);
        $this->logger->info("{$nodeId} database version: {$meta['version']}");
    }
}
