<?php
namespace LazyRecord\Command\DataSourceCommand;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;
use LazyRecord\ConfigLoader;
use LazyRecord\DSN\DSNParser;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use Exception;
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



