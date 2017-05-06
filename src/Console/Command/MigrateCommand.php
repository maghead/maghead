<?php

namespace Maghead\Console\Command;

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
        $this->command('upgrade', 'Maghead\\Console\\Command\\MigrateUpgradeCommand');
        $this->command('downgrade', 'Maghead\\Console\\Command\\MigrateDowngradeCommand');
        $this->command('new', 'Maghead\\Console\\Command\\MigrateNewCommand');
        $this->command('automatic', 'Maghead\\Console\\Command\\MigrateAutomaticCommand');
        $this->command('status', 'Maghead\\Console\\Command\\MigrateStatusCommand');
        $this->command('diff', 'Maghead\\Console\\Command\\MigrateNewFromDiffCommand');
    }

    public function execute()
    {
        $cmd = $this->createCommand('CLIFramework\\Command\\HelpCommand');
        return $cmd->execute($this->getName());
    }
}
