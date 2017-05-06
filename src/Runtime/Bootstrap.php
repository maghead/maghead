<?php

namespace Maghead\Runtime;

use Maghead\TableBuilder\BaseBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\SchemaFinder;
use Maghead\Manager\DataSourceManager;
use Maghead\Runtime\BaseModel;
use Maghead\Runtime\BaseCollection;
use Maghead\Runtime\Config\Config;
use PDOException;
use Exception;
use ArrayAccess;

class Bootstrap
{
    const DEFAULT_CONFIG_FILE = 'db/config/database.yml';

    /**
     * The config object of the current context.
     */
    public static $config;

    public static function getConfig()
    {
        return self::$config;
    }

    public static function setConfig(Config $config)
    {
        self::$config = $config;
    }

    /**
     * Run bootstrap script if it's defined in the config.
     * This is used for the command-line app.
     */
    protected static function loadBootstrapScripts($config)
    {
        if ($scripts = $config->getBootstrapScripts()) {
            foreach ($scripts as $script) {
                require_once $script;
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

    public static function setupDataSources(Config $config, DataSourceManager $dataSourceManager)
    {
        foreach ($config->getDataSources() as $nodeId => $dsConfig) {
            $dataSourceManager->addNode($nodeId, $dsConfig);
        }
    }

    public static function setupGlobalVars(Config $config, DataSourceManager $dataSourceManager)
    {
        BaseModel::$dataSourceManager = $dataSourceManager;
        BaseCollection::$dataSourceManager = $dataSourceManager;
    }

    public static function setup(Config $config)
    {
        self::$config = $config;

        $dataSourceManager = DataSourceManager::getInstance();

        // TODO: this could be moved to Environment class.
        BaseModel::$yamlExtension = extension_loaded('yaml');

        self::setupDataSources($config, $dataSourceManager);
        self::setupGlobalVars($config, $dataSourceManager);
    }

    /**
     * Setup environment for command-line application
     * This could be an override method from Bootstrap class.
     */
    public static function setupForCLI(Config $config)
    {
        self::setup($config);
        self::loadBootstrapScripts($config);
        self::loadSchemaLoader($config);
    }
}
