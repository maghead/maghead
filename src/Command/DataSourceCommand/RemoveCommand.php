<?php

namespace Maghead\Command\DataSourceCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;

class RemoveCommand extends BaseCommand
{
    public function brief()
    {
        return 'Remove node from config file.';
    }

    public function arguments($args)
    {
        $args->add('node-id');
    }

    public function execute($nodeId)
    {
        // force loading data source
        $config = $this->getConfig();

        $manager = new ConfigManager($config);
        $manager->save();

        return true;
    }
}
