<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Runtime\Config\AutoConfigLoader;
use Maghead\Runtime\Config\Config;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\Finder\FileSchemaFinder;
use Maghead\Schema\Finder\ComposerSchemaFinder;
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
     * The default schema finder
     * If we have predefined schema finder in the config, then we should pre-load these classes.
     */
    protected function runDefaultSchemaLoader(Config $config)
    {
        $finders = $config->loadSchemaFinders();
        if (!empty($finders)) {
            foreach ($finders as $finder) {
                $loadedFiles = $finder->find();
                foreach ($loadedFiles as $f) {
                    $this->logger->info("Found schema $f");
                }
            }
        } else {
            // If finders are not defined, then we check if we can load them by composer.json file
            if (file_exists('composer.json')) {
                $this->logger->info('Found composer.json, trying to scan files from the autoload sections...');
                $finder = ComposerSchemaFinder::from('composer.json');
                $loadedFiles = $finder->find();
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
        $this->logger->debug("Loading schemas...");

        // filter file path argumets
        $classes = Utils::filterClassesFromArgs($args);
        $paths = Utils::filterPathsFromArgs($args);

        if (empty($args)) {
            $this->logger->debug("Using default schemas loader...");
            $config = $this->getConfig();
            $this->runDefaultSchemaLoader($config);
        } else if (!empty($paths)) {
            $this->logger->debug("Loading schemas from " . join(', ', $paths));
            $finder = new FileSchemaFinder($paths);
            $finder->find();
        }

        if (count($classes)) {
            $schemas = SchemaCollection::create($classes)->exists()->unique()->buildable()->evaluate();
        } else {
            $schemas = SchemaCollection::declared()->buildable()->evaluate();
        }

        $numberOfSchemas = count($schemas);
        $this->logger->debug("{$numberOfSchemas} schemas loaded");
        return $schemas;
    }
}
