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
        $meta = new MetadataManager($nodeId);
        $this->logger->info("{$nodeId} database version: {$meta['version']}");
    }
}
