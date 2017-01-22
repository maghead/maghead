<?php

namespace Maghead\Command;

use Maghead\Migration\MigrationGenerator;
use Maghead\Console;

class MigrateNewCommand extends MigrateBaseCommand
{
    public function aliases()
    {
        return array('n', 'new');
    }

    public function execute($taskName)
    {
        $dsId = $this->getCurrentDataSourceId();

        $generator = new MigrationGenerator(Console::getInstance()->getLogger(), 'db/migrations');
        $this->logger->info("Creating migration script for '".$taskName."'");
        list($class, $path) = $generator->generate($taskName);
        $this->logger->info("Migration script is generated: $path");
    }
}
