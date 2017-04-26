<?php

namespace Maghead\Command\ShardCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;
use Maghead\DSN\DSNParser;
use PDO;

class MappingCommand extends BaseCommand
{
    public function brief()
    {
        return 'shard mapping commands';
    }

    public function init()
    {
        $this->command('create');
    }

    public function execute()
    {
        return true;
    }
}


