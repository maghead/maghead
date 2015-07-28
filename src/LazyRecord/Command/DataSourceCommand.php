<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;

class DataSourceCommand extends BaseCommand
{

    public function brief()
    {
        return 'data source related commands.';
    }

    public function init()
    {
        // $this->command('add');
        // $this->command('remove');
        // $this->command('set');
        $this->command('set-default');
    }

    public function execute()
    {
        // Force loading data source
        $configLoader = $this->getConfigLoader(true);
        $dataSources = $configLoader->getDataSources();

        // XXX: use Logger->dump method (not released yet)
        foreach ($dataSources as $id => $config) {
            $this->logger->writeln(sprintf("%-10s %s",$id, $config['dsn']));
        }
    }

}

