<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;

class DbCommand extends BaseCommand
{
    public function brief()
    {
        return 'database related commands.';
    }

    public function init()
    {
        $this->command('add');
        $this->command('remove');
        $this->command('create');
        $this->command('recreate');
        $this->command('drop');
        $this->command('list');
    }
}
