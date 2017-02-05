<?php

namespace Maghead\Command\DataSourceCommand;

use Maghead\Command\BaseCommand;
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

    public function execute($defaultDataSource)
    {
        // force loading data source
        $config = $this->getConfig();

        $dataSources = $config->getDataSources();

        if (!in_array($defaultDataSource, array_keys($dataSources))) {
            $this->logger->error("Undefined data source ID: $defaultDataSource");

            return false;
        }

        $config['data_source']['master'] = $defaultDataSource;

        $configLoader->writeToSymbol($config);

        return true;
    }
}
