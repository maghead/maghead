<?php

namespace Maghead;

use Pimple\Container;
use Maghead\Schema\SchemaFinder;
use CLIFramework\Logger;

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

        $this['logger'] = function ($c) {
            return Console::getInstance()->getLogger();
        };

        $this['schema_finder'] = function ($c) {
            $finder = new SchemaFinder();
            $finder->paths = $c['config_loader']->getSchemaPaths() ?: [];

            return $finder;
        };
    }

    public static function getInstance()
    {
        static $instance;
        $instance = new self();

        return $instance;
    }
}
