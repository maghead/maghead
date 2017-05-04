<?php

namespace Maghead\Command;

use Maghead\Manager\MetadataManager;

class VersionCommand extends BaseCommand
{
    public function brief()
    {
        return 'Show database version';
    }

    public function usage()
    {
        return "\tmaghead version\n";
    }

    public function execute($nodeId = 'master')
    {
        $meta = new MetadataManager($nodeId);
        $this->logger->info("{$nodeId} database version: {$meta['version']}");
    }
}
