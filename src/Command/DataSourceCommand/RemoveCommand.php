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



