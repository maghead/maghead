<?php
namespace LazyRecord\Command\DataSourceCommand;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;
use LazyRecord\ConfigLoader;
use LazyRecord\DSN\DSNParser;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
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

        $this->logger->debug("Checking symbol link file: " . $configLoader->symbolFilename);
        if (!file_exists($configLoader->symbolFilename)) {
            $this->logger->error($configLoader->symbolFilename . " is missing. please use lazy build-conf {filename} to update your config link.");
            return false;
        }

        $targetFile = readlink($configLoader->symbolFilename);
        if ($targetFile === false || !file_exists($targetFile)) {
            $this->logger->error('Missing target config file. incorrect symbol link.');
            return false;
        }

        $this->logger->debug("Writing config back to $targetFile");

        $yaml = Yaml::dump($config, $inlineLevel = 4, $indentSpaces = 2, $exceptionOnInvalidType = true);
        if (false === file_put_contents($targetFile, "---\n" . $yaml)) {
            $this->logger->error("YAML config update failed: $targetFile");
            return false;
        }
        $this->logger->info("Config file is updated successfully.");
    }

}



