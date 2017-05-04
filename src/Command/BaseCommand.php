<?php

namespace Maghead\Command;

use CLIFramework\Command;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Schema\SchemaUtils;
use Maghead\Manager\DataSourceManager;
use RuntimeException;
use Maghead\Bootstrap;

class BaseCommand extends Command
{
    protected $config;

    public function prepare()
    {
        // softly load the config file.
        $this->config = SymbolicLinkConfigLoader::load(null, true); // force loading
        Bootstrap::setupForCLI($this->config);
    }

    public function getConfig($force = false)
    {
        if (!$this->config || $force) {
            $this->config = SymbolicLinkConfigLoader::load(null, true); // force loading
        }
        if (!$this->config) {
            throw new \Exception("Can't load symbolic config file.");
        }
        Bootstrap::setupForCLI($this->config);
        return $this->config;
    }

    public function options($opts)
    {
        parent::options($opts);
        $self = $this;
        $opts->add('D|data-source:', 'specify data source id')
            ->defaultValue('master')
            ->validValues(function () use ($self) {
                if ($config = $self->getConfig()) {
                    return array_keys($config->getDataSources());
                }

                return [];
            })
            ;
    }

    public function getCurrentDataSourceId()
    {
        return $this->options->{'data-source'} ?: 'master';
    }

    public function getCurrentQueryDriver()
    {
        $dataSource = $this->getCurrentDataSourceId();
        $dataSourceManager = DataSourceManager::getInstance();

        return $dataSourceManager->getQueryDriver($dataSource);
    }

    public function getCurrentConnection()
    {
        $dataSource = $this->getCurrentDataSourceId();
        $dataSourceManager = DataSourceManager::getInstance();

        return $dataSourceManager->getConnection($dataSource);
    }

    public function findSchemasByArguments(array $arguments)
    {
        return SchemaUtils::findSchemasByArguments($this->getConfig(), $arguments, $this->logger);
    }
}
