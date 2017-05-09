<?php

namespace Maghead\Manager;

use Maghead\Runtime\Config\Config;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Runtime\Config\SymbolicLinkConfigWriter;
use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;
use InvalidArgumentException;

class ConfigManager
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function removeDatabase($nodeId)
    {
        unset($this->config['databases'][$nodeId]);
    }

    public function addDatabaseConfig($nodeId, array $nodeConfig)
    {
        return $this->config['databases'][$nodeId] = $nodeConfig;
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
        return $this->addDatabaseConfig($nodeId, $node);
    }

    public function save($file = null)
    {
        $f = $file ?: $this->config->file;
        return SymbolicLinkConfigWriter::write($this->config, $f);
    }
}
