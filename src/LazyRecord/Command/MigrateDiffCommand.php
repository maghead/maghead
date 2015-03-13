<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateDiffCommand extends BaseCommand
{
    public function brief() {
        return 'Generate a new migration script from diff';
    }

    public function aliases() {
        return array('d', 'di');
    }

}


