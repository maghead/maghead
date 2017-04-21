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

    public function setMasterNode($nodeId)
    {
        $keys = array_keys($this->config['databases']);
        if (!in_array($nodeId, $keys)) {
            throw new InvalidArgumentException("Node $nodeId doesn't exist.");
        }
        $this->config['databases']['master'] = $nodeId;
    }

    public function removeNode($nodeId)
    {
        unset($this->config['databases'][$nodeId]);
    }

    public function addNodeConfig($nodeId, array $nodeConfig)
    {
        $this->config['databases'][$nodeId] = $nodeConfig;
    }

    public function addNode($nodeId, $dsnArg, $opts = array())
    {
        $dsn = DSNParser::parse($dsnArg);

        // The data source array to be added to the config array
        $node = [];
        $node['driver'] = $dsn->getDriver();

        if ($host = $dsn->getHost()) {
            $node['host'] = $host;
        } elseif (isset($opts['host'])) {
            $node['host'] = $opts['host'];
            $dsn->setAttribute('host', $opts['host']);
        }

        if ($port = $dsn->getPort()) {
            $node['port'] = $port;
        } elseif (isset($opts['port'])) {
            $node['port'] = $opts['port'];
            $dsn->setAttribute('port', $opts['port']);
        }

        if ($socket = $dsn->getUnixSocket()) {
            $node['unix_socket'] = $socket;
        }

        // MySQL only attribute
        if ($dbname = $dsn->getAttribute('dbname')) {
            $node['database'] = $dbname;
        } elseif (isset($opts['dbname'])) {
            $node['database'] = $opts['dbname'];
            $dsn->setAttribute('dbname', $opts['dbname']);
        }

        if (isset($opts['user'])) {
            $node['user'] = $opts['user'];
        }
        if (isset($opts['password'])) {
            $node['password'] = $opts['password'];
        } else if (isset($opts['pass'])) {
            $node['password'] = $opts['pass'];
        }

        $node['dsn'] = $dsn->__toString();

        switch ($dsn->getDriver()) {
            case 'mysql':
                // $this->logger->debug('Setting connection options: PDO::MYSQL_ATTR_INIT_COMMAND');
                $node['connection_options'] = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];
            break;
        }
        $this->config['databases'][$nodeId] = $node;
    }

    public function save($file = null)
    {
        $f = $file ?: $this->config->file;
        return ConfigLoader::writeToSymbol($this->config, $f);
    }
}
