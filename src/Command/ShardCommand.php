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
        // $this->command('allocate');
        // $this->command('move');
    }

    public function execute()
    {
        $config = $this->getConfig(true);
        // $this->logger->writeln(sprintf('%-10s %s', $id, $config['dsn']));
    }
}
