<?php
namespace LazyRecord;
use Pimple\Container;

use LazyRecord\ConfigLoader;

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
    }

    static public function getInstance()
    {
        static $instance;
        $instance = new self;
        return $instance;
    }
}






