<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Runtime\Config\AutoConfigLoader;
use Maghead\Runtime\Config\Config;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\Loader\FileSchemaLoader;
use Maghead\Schema\Loader\ComposerSchemaLoader;
use Maghead\Runtime\Bootstrap;
use Maghead\Manager\DataSourceManager;
use Maghead\Utils;
use RuntimeException;

class BaseCommand extends Command
{
    public $dataSourceManager;

    /**
     * @override
     */
    public function createCommand($commandClass)
    {
        $cmd = parent::createCommand($commandClass);

        if ($cmd instanceof BaseCommand) {
            $cmd->dataSourceManager = $this->dataSourceManager;
        }
        return $cmd;
    }

    public function prepare()
    {
        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $config = $this->getApplication()->loadConfig();
        $this->dataSourceManager = DataSourceManager::getInstance();
        return $config;
    }

    /**
     * Return the config object in the current context
     */
    protected function getConfig($reload = false)
    {
        $config = Bootstrap::getConfig();
        if (!$config || $reload) {
            return $this->loadConfig();
        }
        return $config;
    }


    /**
     * The default schema loader
     * If we have predefined schema loaders in the config, then we should pre-load these classes.
     */
    protected function runDefaultSchemaLoader(Config $config)
    {
        $loaders = $config->loadSchemaLoaders();
        if (!empty($loaders)) {
            foreach ($loaders as $loader) {
                $loadedFiles = $loader->load();
                foreach ($loadedFiles as $f) {
                    $this->logger->info("Found schema $f");
                }
            }
        } else {
            // If loaders are not defined, then we check if we can load them by composer.json file
            if (file_exists('composer.json')) {
                $this->logger->info('Found composer.json, trying to scan files from the autoload sections...');
                $loader = ComposerSchemaLoader::from('composer.json');
                $loadedFiles = $loader->load();
                foreach ($loadedFiles as $f) {
                    $this->logger->info("Found schema $f");
                }
            }
        }
    }


    /**
     * Loads schemas from arguments. Two types of the argument are supported: file and class name
     *
     * @return SchemaCollection
     */
    protected function loadSchemasFromArguments(array $args)
    {
        $config = $this->getConfig();

        $this->runDefaultSchemaLoader($config);

        // filter file path argumets
        $paths = Utils::filterPathsFromArgs($args);
        $classes = Utils::filterClassesFromArgs($args);

        if (empty($paths)) {
            $paths = $config->getSchemaPaths();
        }
        if (!empty($paths)) {
            $loader = new FileSchemaLoader($paths);
            $loadedFiles = $loader->load();
        }

        return SchemaUtils::argumentsToSchemaObjects($classes)->notForTest();
    }
}
