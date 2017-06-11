<?php

namespace Maghead\Runtime;

use Maghead\TableBuilder\BaseBuilder;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\Finder\FileSchemaFinder;
use Maghead\Manager\DataSourceManager;
use Maghead\Runtime\Model;
use Maghead\Runtime\Collection;
use Maghead\Runtime\Config\Config;
use PDOException;
use Exception;
use ArrayAccess;

class Bootstrap
{
    const DEFAULT_SITE_CONFIG_FILE = 'db/config/site_database.yml';

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
     * Remove the config from the context.
     */
    public static function removeConfig()
    {
        self::$config = null;
    }

    /**
     * Run bootstrap script if it's defined in the config.
     * This is used for the command-line app.
     */
    protected static function loadBootstrapScript($config)
    {
        if ($script = $config->getBootstrapScript()) {
            require_once $script;
        }
    }

    /**
     * load external schema loader.
     */
    protected static function loadExternalSchemaFinder($config)
    {
        $finders = $config->loadSchemaFinders();

        if (empty($finders)) {
            return false;
        }

        foreach ($finders as $finder) {
            $finder->find();
        }

        return true;
    }

    protected static function loadSchemaFromFinder($config)
    {
        // Load default schema loader
        $paths = $config->getSchemaPaths();
        if (!empty($paths)) {
            $finder = new FileSchemaFinder($paths);
            $files = $finder->find();
        }
    }

    protected static function loadSchemaFinders($config)
    {
        if (!self::loadExternalSchemaFinder($config)) {
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
        Model::$dataSourceManager = $dataSourceManager;
        Collection::$dataSourceManager = $dataSourceManager;
    }

    /**
     * Setup the environment for models.
     *
     * If DataSourceManager is ignored, then the DataSourceManager singleton will be used.
     */
    public static function setup(Config $config, DataSourceManager $dataSourceManager = null)
    {
        self::$config = $config;

        if (!$dataSourceManager) {
            $dataSourceManager = DataSourceManager::getInstance();
        }

        // TODO: this could be moved to Environment class.
        Model::$yamlExtension = extension_loaded('yaml');

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
        self::loadBootstrapScript($config);
        self::loadSchemaFinders($config);
    }
}
