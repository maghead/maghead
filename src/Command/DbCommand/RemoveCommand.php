<?php

namespace Maghead\Command\DbCommand;

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
        $config = $this->getConfig(true);

        $manager = new ConfigManager($config);
        $manager->removeDatabase($nodeId);
        $manager->save();
        return true;
    }
}
