<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Migration\MigrationGenerator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Command\BaseCommand;

class MigrateCommand extends BaseCommand
{
    public function brief()
    {
        return 'Database migration commands';
    }

    public function aliases() 
    {
        return array('m');
    }

    public function init() {
        parent::init();
        $this->command('upgrade', 'LazyRecord\\Command\\MigrateUpgradeCommand');
        $this->command('downgrade', 'LazyRecord\\Command\\MigrateDowngradeCommand');
        $this->command('new', 'LazyRecord\\Command\\MigrateNewCommand');
        $this->command('status', 'LazyRecord\\Command\\MigrateStatusCommand');
        $this->command('diff', 'LazyRecord\\Command\\MigrateDiffCommand');
    }
}

