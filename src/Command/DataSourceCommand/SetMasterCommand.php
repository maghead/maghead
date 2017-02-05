<?php

namespace Maghead\Command\DataSourceCommand;

use Maghead\Command\BaseCommand;
use Maghead\Manager\ConfigManager;
use PDO;

class SetMasterCommand extends BaseCommand
{
    public function brief()
    {
        return 'set master data source for PDO connections.';
    }

    public function arguments($args)
    {
        $args->add('datasource');
    }

    public function execute($newMaster)
    {
        // force loading data source
        $config = $this->getConfig();

        $manager = new ConfigManager($config);
        $manager->setMasterNode($newMaster);
        $manager->save();
        return true;
    }
}
