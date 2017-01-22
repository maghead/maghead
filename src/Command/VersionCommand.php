<?php

namespace Maghead\Command;

use Maghead\Metadata;

class VersionCommand extends BaseCommand
{
    public function brief()
    {
        return 'Show database version';
    }

    public function usage()
    {
        return "\tlazy version\n";
    }

    public function options($opts)
    {
        $opts->add('D|data-source:', 'specify data source id');
    }

    public function execute()
    {
        $dsId = $this->options->{'data-source'} ?: 'default';
        $meta = new Metadata($dsId);
        $this->logger->info('database version: '.$meta['version']);
    }
}
