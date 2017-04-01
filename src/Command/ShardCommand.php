<?php

namespace Maghead\Command;

use CLIFramework\Command;

class ShardCommand extends BaseCommand
{
    public function brief()
    {
        return 'shard related commands.';
    }

    public function init()
    {
        $this->command('allocate');
        $this->command('clone');
        $this->command('prune');
        $this->command('split');
    }

    public function execute()
    {
        $cmd = $this->createCommand('CLIFramework\\Command\\HelpCommand');
        $cmd->execute($this->getName());
    }
}
