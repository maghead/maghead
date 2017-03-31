<?php

namespace Maghead;

use Maghead\TableBuilder\BaseBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\SchemaFinder;
use Maghead\Manager\ConnectionManager;
use Maghead\Runtime\BaseModel;
use Maghead\Runtime\BaseCollection;
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
    protected static function loadBootstrap($config)
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
    protected static function loadExternalSchemaLoader($config)
    {
        if (isset($config['schema']['loader'])) {
            require_once $config['schema']['loader'];

            return true;
        }

        return false;
    }

    protected static function loadSchemaFromFinder($config)
    {
        // Load default schema loader
        $paths = $config->getSchemaPaths();
        if (!empty($paths)) {
            $finder = new SchemaFinder($paths);
            $finder->find();
        }
    }

    protected static function loadSchemaLoader($config)
    {
        if (!self::loadExternalSchemaLoader($config)) {
            self::loadSchemaFromFinder($config);
        }
    }

    public static function setupDataSources(Config $config, ConnectionManager $connectionManager)
    {
        foreach ($config->getDataSources() as $nodeId => $dsConfig) {
            $connectionManager->addNode($nodeId, $dsConfig);
        }
        if ($nodeId = $config->getMasterDataSourceId()) {
            $connectionManager->setMasterNodeId($nodeId);
        }
    }


    public static function setupGlobalVars(Config $config, ConnectionManager $connectionManager)
    {
        BaseModel::$connectionManager = $connectionManager;
        BaseCollection::$connectionManager = $connectionManager;
    }

    public static function setup(Config $config)
    {
        $connectionManager = ConnectionManager::getInstance();

        // TODO: this could be moved to Environment class.
        BaseModel::$yamlExtension = extension_loaded('yaml');

        self::setupDataSources($config, $connectionManager);
        self::setupGlobalVars($config, $connectionManager);
    }

    /**
     * Setup environment for command-line application
     * This could be an override method from Bootstrap class.
     */
    public static function setupForCLI(Config $config)
    {
        self::setup($config);
        self::loadBootstrap($config);
        self::loadSchemaLoader($config);
    }
}
