<?php

namespace Maghead\Command;

use CLIFramework\Command;
use Maghead\ConfigLoader;
use Maghead\Schema\SchemaUtils;
use Maghead\ConnectionManager;
use RuntimeException;

class BaseCommand extends Command
{
    /**
     * @var ConfigLoader
     */
    protected $config;

    public function prepare()
    {
        // softly load the config file.
        $this->config = ConfigLoader::getInstance();
        $this->config->loadFromSymbol(true); // force loading
        if ($this->config->isLoaded()) {
            $this->config->initForBuild();
        }
    }

    public function getConfigLoader($required = true)
    {
        if (!$this->config) {
            $this->config = ConfigLoader::getInstance();
            $this->config->loadFromSymbol(true); // force loading
            if (!$this->config->isLoaded() && $required) {
                throw new RuntimeException("ConfigLoader did not loaded any config file. Can't initialize the settings.");
            }
        }

        return $this->config;
    }

    public function options($opts)
    {
        parent::options($opts);
        $self = $this;
        $opts->add('D|data-source:', 'specify data source id')
            ->validValues(function () use ($self) {
                if ($config = $self->getConfigLoader()) {
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
        return SchemaUtils::findSchemasByArguments($this->getConfigLoader(), $arguments, $this->logger);
    }
}
