<?php

namespace Maghead;

use Maghead\TableBuilder\BaseBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\SchemaFinder;
use Maghead\Manager\ConnectionManager;
use CLIFramework\Logger;

use ConfigKit\ConfigCompiler;
use PDOException;
use Exception;
use ArrayAccess;

class Bootstrap
{
    /**
     * Run bootstrap script if it's defined in the config.
     * This is used for the command-line app.
     */
    static protected function loadBootstrap($config)
    {
        if (isset($config['bootstrap'])) {
            foreach ((array) $config['bootstrap'] as $bootstrap) {
                require_once $bootstrap;
            }
        }
    }

    /**
     * load external schema loader.
     */
    static protected function loadExternalSchemaLoader($config)
    {
        if (isset($config['schema']['loader'])) {
            require_once $config['schema']['loader'];

            return true;
        }

        return false;
    }

    static protected function loadSchemaFromFinder($config)
    {
        // Load default schema loader
        $paths = $config->getSchemaPaths();
        if (!empty($paths)) {
            $finder = new SchemaFinder($paths);
            $finder->find();
        }
    }

    static protected function loadSchemaLoader($config)
    {
        if (!self::loadExternalSchemaLoader($config)) {
            self::loadSchemaFromFinder($config);
        }
    }

    static public function setupDataSources(Config $config, ConnectionManager $connectionManager)
    {
        foreach ($config->getDataSources() as $nodeId => $dsConfig) {
            $connectionManager->addDataSource($nodeId, $dsConfig);
        }
        if ($nodeId = $config->getDefaultDataSourceId()) {
            $connectionManager->setDefaultDataSourceId($nodeId);
        }
    }


    static public function setupGlobalVars(Config $config, ConnectionManager $connectionManager)
    {
        BaseModel::$connectionManager = $connectionManager;
        BaseCollection::$connectionManager = $connectionManager;
    }

    static public function run(Config $config, $connectOnly = false)
    {
        $connectionManager = ConnectionManager::getInstance();
        self::setupDataSources($config, $connectionManager);
        self::setupGlobalVars($config, $connectionManager);
        if (PHP_SAPI === "cli" && !$connectOnly) {
            self::loadBootstrap($config);
            self::loadSchemaLoader($config);
        }
    }
}
