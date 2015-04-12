<?php
namespace LazyRecord;
use Pimple\Container;

use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\RuntimeSchema;

class ServiceContainer extends Container
{
    public function __construct()
    {
        $this['config_loader'] = function ($c) {
            $config = ConfigLoader::getInstance();
            $config->loadFromSymbol(true); // force loading
            if ($config->isLoaded()) {
                $config->initForBuild();
            }
            return $config;
        };

        $this['schema_finder'] = function($c) {
            $finder = new SchemaFinder;
            $finder->paths = $c['config_loader']->getSchemaPaths();
            return $finder;
        };
    }

    static public function getInstance()
    {
        static $instance;
        $instance = new self;
        return $instance;
    }
}






