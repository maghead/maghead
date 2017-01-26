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
        $config = $this->getConfig();

        $stash = $config->getStash();
        unset($stash['data_source']['nodes'][$dataSourceId]);

        $configLoader->writeToSymbol($config);
        return true;
    }
}
