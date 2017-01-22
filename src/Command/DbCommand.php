<?php

namespace Maghead\Command;

use CLIFramework\Command;

class DbCommand extends BaseCommand
{
    public function brief()
    {
        return 'database related commands.';
    }

    public function init()
    {
        $this->command('create');
        $this->command('recreate');
        $this->command('drop');
    }

    public function execute()
    {
    }
}
