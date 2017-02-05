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

    public function __construct(Config $config)
    {
        $this->config = $config;
    }


    public function setMasterNode($nodeId)
    {
        $keys = array_keys($this->config['data_source']['nodes']);
        if (!in_array($nodeId, $keys)) {
            throw new InvalidArgumentException("Node $nodeId doesn't exist.");
        }
        $this->config['data_source']['master'] = $nodeId;
    }

    public function removeNode($nodeId)
    {
        unset($this->config['data_source']['nodes'][$nodeId]);
    }

    public function addNode($nodeId, $dsnstr, $opts = array())
    {
        $dsn = DSNParser::parse($dsnstr);

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
            $node['pass'] = $opts['password'];
        }

        $node['dsn'] = $dsn->__toString();

        switch ($dsn->getDriver()) {
            case 'mysql':
                // $this->logger->debug('Setting connection options: PDO::MYSQL_ATTR_INIT_COMMAND');
                $node['connection_options'] = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];
            break;
        }
        $this->config['data_source']['nodes'][$nodeId] = $node;
    }

    public function save($file = null)
    {
        return ConfigLoader::writeToSymbol($this->config, $file);
    }
}
