<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateDowngradeCommand extends BaseCommand
{
    public function brief() {
        return 'Run downgrade migration scripts.';
    }

    public function aliases() {
        return array('d', 'down');
    }

}


