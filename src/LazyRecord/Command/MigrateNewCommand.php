<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateNewCommand extends BaseCommand
{

    public function aliases() {
        return array('n', 'new');
    }

}


