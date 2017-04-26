<?php

namespace Maghead\Command;

use CLIFramework\Command;

class ShardCommand extends BaseCommand
{
    public function brief()
    {
        return 'shard commands';
    }

    public function options($opts)
    {
        // $opts->add('v|verbose', 'Display verbose information');
    }

    public function init()
    {
        $this->command('mapping');
        $this->command('allocate');
        // $this->command('move');
    }

    public function execute()
    {
        $cmd = $this->createCommand('CLIFramework\\Command\\HelpCommand');
        $cmd->execute($this->getName());
    }
}
