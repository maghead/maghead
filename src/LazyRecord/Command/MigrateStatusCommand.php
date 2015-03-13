<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateStatusCommand extends BaseCommand
{

    public function brief() {  return 'Show current migration status.'; }

    public function aliases() {
        return array('s', 'st');
    }

}


