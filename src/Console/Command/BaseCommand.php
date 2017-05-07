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
        // softly load the config file.
        if (file_exists('db/appId')) {
            $appId = file_get_contents('db/appId');
            $config = AutoConfigLoader::load($appId, SymbolicLinkConfigLoader::ANCHOR_FILENAME);
        } else {
            $config = SymbolicLinkConfigLoader::load(null, true); // force loading
        }
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

    protected function findSchemasByArguments(array $args)
    {
        $config = $this->getConfig();
        $classes = SchemaUtils::argumentsToSchemaObjects($args);

        // filter file path argumets
        $paths = array_filter($args, 'file_exists');
        if (empty($paths)) {
            $paths = $config->getSchemaPaths();
        }

        if (!empty($paths)) {
            $finder = new SchemaFinder($paths);
            $finder->find();
        }

        return SchemaLoader::loadDeclaredSchemas();
    }
}
