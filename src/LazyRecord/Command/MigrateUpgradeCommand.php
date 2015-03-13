<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateUpgradeCommand extends BaseCommand
{

    public function aliases() {
        return array('u', 'up');
    }

}


