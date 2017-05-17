<?php

namespace Maghead\Console\Command;

use Maghead\Migration\MigrationGenerator;
use Maghead\Console\Application;

class MigrateNewCommand extends MigrateBaseCommand
{
    public function aliases()
    {
        return array('n', 'new');
    }

    public function execute($taskName)
    {
        $generator = new MigrationGenerator($this->logger, $this->options->{'script-dir'});
        $this->logger->info("Creating migration script for '".$taskName."'");
        list($class, $path) = $generator->generate($taskName);
        $this->logger->info("Migration script is generated: $path");
    }
}
