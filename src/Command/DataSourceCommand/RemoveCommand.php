<?php

namespace Maghead\Command\DataSourceCommand;

use Maghead\Command\BaseCommand;

class RemoveCommand extends BaseCommand
{
    public function brief()
    {
        return 'Remove data source from config file.';
    }

    public function arguments($args)
    {
        $args->add('data-source-id');
    }

    public function execute($dataSourceId)
    {
        // force loading data source
        $configLoader = $this->getConfigLoader(true);

        $config = $configLoader->getConfigStash();
        unset($config['data_source']['nodes'][$dataSourceId]);

        $configLoader->setConfigStash($config);
        $configLoader->writeToSymbol();

        return true;
    }
}
