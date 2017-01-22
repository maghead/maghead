<?php

namespace Maghead\Command;

use CLIFramework\Command;

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

    public function init()
    {
        parent::init();
        $this->command('upgrade', 'Maghead\\Command\\MigrateUpgradeCommand');
        $this->command('downgrade', 'Maghead\\Command\\MigrateDowngradeCommand');
        $this->command('new', 'Maghead\\Command\\MigrateNewCommand');
        $this->command('automatic', 'Maghead\\Command\\MigrateAutomaticCommand');
        $this->command('status', 'Maghead\\Command\\MigrateStatusCommand');
        $this->command('diff', 'Maghead\\Command\\MigrateNewFromDiffCommand');
    }

    public function execute()
    {
    }
}
