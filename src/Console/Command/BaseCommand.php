<?php

namespace Maghead\Console\Command;

use CLIFramework\Command;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Runtime\Config\AutoConfigLoader;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaLoader;
use Maghead\Schema\SchemaFinder;
use Maghead\Manager\DataSourceManager;
use RuntimeException;
use Maghead\Runtime\Bootstrap;

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
        // $ttl = false disable the apcu cache
        $config = AutoConfigLoader::load(SymbolicLinkConfigLoader::ANCHOR_FILENAME, false);
        Bootstrap::setupForCLI($config);
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

    protected function loadSchemasFromArguments(array $args)
    {
        $config = $this->getConfig();

        // filter file path argumets
        $paths = array_filter($args, 'file_exists');
        if (empty($paths)) {
            $paths = $config->getSchemaPaths();
        }
        if (!empty($paths)) {
            $finder = new SchemaFinder($paths);
            $finder->find();
        }

        $classes = array_filter($args, function($a) { return class_exists($a, true); });
        return SchemaUtils::argumentsToSchemaObjects($classes)->notForTest()->getArrayCopy();
    }
}
