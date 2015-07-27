<?php
namespace LazyRecord\Command;

use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;

class DbCommand extends BaseCommand
{

    public function brief() 
    {
        return 'database related commands.';
    }


    public function init()
    {
        $this->command('create');
        $this->command('drop');
    }

    public function execute() { }

}

