<?php

namespace Maghead\Console;

use Maghead\Runtime\Config\SymbolicLinkConfigLoader;
use Maghead\Runtime\Config\AutoConfigLoader;
use Maghead\Runtime\Bootstrap;
use Maghead\Manager\DataSourceManager;

class Application extends \CLIFramework\Application
{
    const NAME = 'Maghead';
    const VERSION = '4.0.x';

    protected $dataSourceManager;

    public function brief()
    {
        return 'Maghead ORM';
    }

    public function options($opts)
    {
        parent::options($opts);
        $opts->add('c|config:','the path to the config file')
            ->isa('file');
    }

    public function loadConfig()
    {
        // $ttl = false disable the apcu cache
        $configFile = $this->options->config ?: SymbolicLinkConfigLoader::ANCHOR_FILENAME;

        if (!file_exists($configFile)) {
            throw new \Exception("File $configFile doesn't exist.");
        }

        $config = AutoConfigLoader::load($configFile, false);
        Bootstrap::setupForCLI($config);
        $this->dataSourceManager = DataSourceManager::getInstance();
        return $config;
    }

    public function init()
    {
        parent::init();

        // The order of the command list follows the workflow.

        $this->command('init');

        $this->command('use');
        $this->command('config');

        $this->command('schema'); // the schema command builds all schema files and shows a diff after building new schema
        $this->command('seed');
        $this->command('sql');
        $this->command('diff');
        $this->command('migrate');
        $this->command('meta');
        $this->command('version');
        $this->command('db');
        $this->command('shard');
        $this->command('table');
        $this->command('index');
        $this->command('shard');

    }
}
