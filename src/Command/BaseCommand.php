<?php

namespace Maghead\Command;

use CLIFramework\Command;
use Maghead\ConfigLoader;
use Maghead\Schema\SchemaUtils;
use Maghead\Manager\ConnectionManager;
use RuntimeException;
use Maghead\Bootstrap;

class BaseCommand extends Command
{
    /**
     * @var ConfigLoader
     */
    protected $configLoader;

    protected $config;

    public function prepare()
    {
        // softly load the config file.
        $this->configLoader = ConfigLoader::getInstance();
        $this->config = ConfigLoader::loadFromSymbol(true); // force loading
        Bootstrap::setupForCLI($this->config);
    }

    public function getConfig()
    {
        if (!$this->config) {
            $this->configLoader = ConfigLoader::getInstance();
            $this->config = ConfigLoader::loadFromSymbol(true); // force loading
            Bootstrap::setupForCLI($this->config);
        }

        return $this->config;
    }

    public function options($opts)
    {
        parent::options($opts);
        $self = $this;
        $opts->add('D|data-source:', 'specify data source id')
            ->validValues(function () use ($self) {
                if ($config = $self->getConfig()) {
                    return $config->getDataSourceIds();
                }

                return array();
            })
            ;
    }

    public function getCurrentDataSourceId()
    {
        return $this->options->{'data-source'} ?: 'default';
    }

    public function getCurrentQueryDriver()
    {
        $dataSource = $this->getCurrentDataSourceId();
        $connectionManager = ConnectionManager::getInstance();

        return $connectionManager->getQueryDriver($dataSource);
    }

    public function getCurrentConnection()
    {
        $dataSource = $this->getCurrentDataSourceId();
        $connectionManager = ConnectionManager::getInstance();

        return $connectionManager->getConnection($dataSource);
    }

    public function findSchemasByArguments(array $arguments)
    {
        return SchemaUtils::findSchemasByArguments($this->getConfig(), $arguments, $this->logger);
    }
}
