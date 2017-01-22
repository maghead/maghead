<?php

namespace Maghead\Command\DataSourceCommand;

use Maghead\Command\BaseCommand;
use PDO;

class SetDefaultCommand extends BaseCommand
{
    public function brief()
    {
        return 'set default data source for PDO connections.';
    }

    public function arguments($args)
    {
        $args->add('default-datasource');
    }

    public function execute($defaultDataSource)
    {
        // force loading data source
        $configLoader = $this->getConfigLoader(true);

        $dataSources = $configLoader->getDataSources();

        if (!in_array($defaultDataSource, array_keys($dataSources))) {
            $this->logger->error("Undefined data source ID: $defaultDataSource");

            return false;
        }

        $config = $configLoader->getConfigStash();
        $config['data_source']['default'] = $defaultDataSource;

        $configLoader->setConfigStash($config);
        $configLoader->writeToSymbol();

        return true;
    }
}
