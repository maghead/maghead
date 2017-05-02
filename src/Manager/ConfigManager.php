<?php

namespace Maghead\Manager;

use Maghead\Config;
use Maghead\ConfigLoader;
use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;
use PDO;
use InvalidArgumentException;

class ConfigManager
{
    protected $config;

    public function __construct($arg)
    {
        if (is_string($arg)) {
            $this->config = ConfigLoader::loadFromFile($arg);
        } else if ($arg instanceof Config) {
            $this->config = $arg;
        } else {
            throw new InvalidArgumentException("Constructor argument need to be a valid config path or a config object.");
        }
    }

    public function removeDatabase($nodeId)
    {
        unset($this->config['databases'][$nodeId]);
    }

    public function addDatabaseConfig($nodeId, array $nodeConfig)
    {
        $this->config['databases'][$nodeId] = $nodeConfig;
    }

    private function reconcileNodeConfigKeys(array $node)
    {
        if (isset($node['dbname']) && $node['dbname']) {
            $node['database'] = $node['dbname'];
            unset($node['dbname']);
        }
        if (isset($node['pass']) && $node['pass']) {
            $node['password'] = $node['pass'];
            unset($node['pass']);
        }
        return $node;
    }

    public function addDatabase($nodeId, $dsnArg, array $opts = null)
    {
        $dsn = DSNParser::parse($dsnArg);
        $node = $dsn->toConfig();
        if ($opts) {
            $opts = $this->reconcileNodeConfigKeys($opts);
            $node = array_merge($node, $opts);
        }
        $node = DSN::update($node);
        $this->config['databases'][$nodeId] = $node;
        return $node;
    }

    public function save($file = null)
    {
        $f = $file ?: $this->config->file;
        return ConfigLoader::writeToSymbol($this->config, $f);
    }
}
