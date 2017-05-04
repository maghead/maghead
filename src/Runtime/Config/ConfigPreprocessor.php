<?php

namespace Maghead\Runtime\Config;

use Maghead\DSN\DSN;
use Maghead\DSN\DSNParser;
use PDO;

class ConfigPreprocessor
{
    public static function preprocess(array $config)
    {
        if (isset($config['databases'])) {
            $config['databases'] = self::normalizeNodeConfigArray($config['databases']);
        }

        if (isset($config['instance'])) {
            $config['instance'] = self::normalizeNodeConfigArray($config['instance']);
        }

        return $config;
    }

    private static function updateDSN(array $nodeConfig)
    {
        $nodeConfig['dsn'] = DSN::create($nodeConfig)->__toString();
        return $nodeConfig;
    }

    private static function normalizeNodeConfig(array $config)
    {
        // if DSN is defined, then we use the DSN to update the node config
        $dsn = null;
        if (isset($config['dsn'])) {
            $dsn = DSNParser::parse($config['dsn']);
            if (!isset($config['driver'])) {
                $config['driver'] = $dsn->getDriver();
            }
            if ($host = $dsn->getHost()) {
                $config['host'] = $host;
            }
            if ($port = $dsn->getPort()) {
                $config['port'] = $port;
            }
            if ($socket = $dsn->getUnixSocket()) {
                $config['unix_socket'] = $socket;
            }
            if ($db = $dsn->getDBName()) {
                $config['database'] = $db;
            }
        }

        // rewrite config
        // compatible keys for username and password
        if (isset($config['username']) && $config['username']) {
            $config['user'] = $config['username'];
        }

        // alias socket to unix_socket
        if (isset($config['socket'])) {
            $config['unix_socket'] = $config['socket'];
        }
        // alias pass to pasword
        if (isset($config['pass'])) {
            $config['password'] = $config['pass'];
            unset($config['pass']);
        }

        if (isset($config['dbname'])) {
            $config['database'] = $config['dbname'];
            unset($config['dbname']);
        }

        // predefined nulls
        if (!isset($config['user'])) {
            $config['user'] = null;
        }
        if (!isset($config['password'])) {
            $config['password'] = null;
        }
        if (!isset($config['query_options'])) {
            $config['query_options'] = [];
        }
        if (!isset($config['connection_options'])) {
            $config['connection_options'] = [];
        }

        $opts = [];
        foreach ($config['connection_options'] as $key => $val) {
            if (is_numeric($key)) {
                $opts[$key] = $val;
            } else {
                $opts[constant($key)] = $val;
            }
        }
        $config['connection_options'] = $opts; // new connect options
        if ('mysql' === $config['driver']) {
            $config['connection_options'][ PDO::MYSQL_ATTR_INIT_COMMAND ] = 'SET NAMES utf8';
        }


        // Expand read/write node config
        // compile the dsn for that node.
        if (isset($config['read']) && isset($config['write'])) {
            $readServers = (array) $config['read'];
            $writeServers = (array) $config['write'];

            unset($config['read']);
            unset($config['write']);

            $readNodes = [];
            // Cast the server list.
            foreach ($readServers as $serverAddress) {
                $c = array_merge($config, []);
                $c['host'] = $serverAddress;
                $readNodes[] = DSN::update($c);
            }

            $writeNodes = [];
            foreach ($writeServers as $serverAddress) {
                $c = array_merge($config, []);
                $c['host'] = $serverAddress;
                $writeNodes[] = DSN::update($c);
            }

            $config['read'] = $readNodes;
            $config['write'] = $writeNodes;
        }

        // build dsn string for PDO
        // if the DSN is not defined, compile the information into dsn if possible.
        if (!isset($config['write']) && !isset($config['read'])) {
            if (!isset($config['dsn'])) {
                $config = DSN::update($config);
            }
        }
        return $config;
    }


    /**
     * This method is used for compiling config array.
     *
     * @param array PHP array from yaml config file
     */
    private static function normalizeNodeConfigArray(array $dbconfig)
    {
        $newconfig = [];
        foreach ($dbconfig as $nodeId => $config) {
            $newconfig[$nodeId] = self::normalizeNodeConfig($config);
        }
        return $newconfig;
    }
}
