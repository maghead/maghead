<?php

namespace Maghead\Runtime;

use Pimple\Container;
use Maghead\Schema\SchemaFinder;
use CLIFramework\Logger;
use Maghead\Runtime\Config\SymbolicLinkConfigLoader;

class ServiceContainer extends Container
{
    public function __construct()
    {
        $this['config'] = function ($c) {
            $config = SymbolicLinkConfigLoader::load(true); // force loading
            Bootstrap::setup($config);
            return $config;
        };

        $this['logger'] = function ($c) {
            return Application::getInstance()->getLogger();
        };

        $this['schema_finder'] = function ($c) {
            $finder = new SchemaFinder();
            $finder->paths = $c['config']->getSchemaPaths() ?: [];

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
