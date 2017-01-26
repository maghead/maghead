<?php

namespace Maghead;

use Maghead\SqlBuilder\BaseBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\SchemaFinder;
use CLIFramework\Logger;

use ConfigKit\ConfigCompiler;
use PDOException;
use Exception;
use ArrayAccess;

class Bootstrap
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function loadDataSources(ConnectionManager $connectionManager)
    {
        foreach ($this->config->getDataSources() as $nodeId => $dsConfig) {
            $connectionManager->addDataSource($nodeId, $dsConfig);
        }
        if ($nodeId = $this->config->getDefaultDataSourceId()) {
            $connectionManager->setDefaultDataSourceId($nodeId);
        }
    }

    /**
     * Run bootstrap script if it's defined in the config.
     * This is used for the command-line app.
     */
    protected function loadBootstrap()
    {
        if (isset($this->config['bootstrap'])) {
            foreach ((array) $this->config['bootstrap'] as $bootstrap) {
                require_once $bootstrap;
            }
        }
    }

    /**
     * load external schema loader.
     */
    protected function loadExternalSchemaLoader()
    {
        if (isset($this->config['schema']['loader'])) {
            require_once $this->config['schema']['loader'];

            return true;
        }

        return false;
    }

    protected function loadSchemaFromFinder()
    {
        // Load default schema loader
        $paths = $this->config->getSchemaPaths();
        if (!empty($paths)) {
            $finder = new SchemaFinder($paths);
            $finder->find();
        }
    }

    protected function loadSchemaLoader()
    {
        if (!$this->loadExternalSchemaLoader()) {
            $this->loadSchemaFromFinder();
        }
    }

    public function init()
    {
        $this->loadDataSources(ConnectionManager::getInstance());
        if (PHP_SAPI === "cli") {
            $this->loadBootstrap();
            $this->loadSchemaLoader();
        }
    }
}
