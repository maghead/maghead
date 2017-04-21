<?php

namespace Maghead;

use ConfigKit\ConfigCompiler;
use Exception;
use PDO;
use Maghead\DSN\DSN;
use Maghead\DSN\DSNParser;
use Symfony\Component\Yaml\Yaml;
use Maghead\Schema\SchemaFinder;

/**
 * ConfigLoader provides methods for loading the config file.
 *
 * We made ConfigLoader with instance methods because we have to save the
 * latest loaded config object for DeclareSchema to check some configuration
 * values.
 *
 * You may use ConfigLoader::getInstance() to get the singleton instance.
 */
class ConfigLoader
{
    const ANCHOR_FILENAME = '.lazy.yml';

    public static $inlineLevel = 4;

    public static $indentSpaces = 2;

    public static $currentConfig;

    public static function writeToSymbol(Config $config, $targetFile = null)
    {
        if (!$targetFile) {
            if (!file_exists(self::ANCHOR_FILENAME)) {
                throw new Exception('symbol link '.self::ANCHOR_FILENAME.' does not exist.');
            }
            $targetFile = readlink(self::ANCHOR_FILENAME);
        }
        if (!$targetFile || !file_exists($targetFile)) {
            throw new Exception('Missing target config file. incorrect symbol link.');
        }

        $yaml = Yaml::dump($config->stash, self::$inlineLevel, self::$indentSpaces);
        if (false === file_put_contents($targetFile, "---\n".$yaml)) {
            throw new Exception("YAML config update failed: $targetFile");
        }

        return true;
    }


    /**
     * This is used when running command line application
     */
    public static function loadFromSymbol($force = false)
    {
        if (file_exists(self::ANCHOR_FILENAME)) {
            return self::loadFromFile(realpath(self::ANCHOR_FILENAME), $force);
        }
    }

    /**
     * Load config from array directly.
     *
     * @param array $config
     */
    public static function loadFromArray(array $config)
    {
        return self::$currentConfig = new Config(self::preprocessConfig($config));
    }

    /**
     * Load config from the YAML config file...
     *
     * @param string $file
     */
    public static function loadFromFile($sourceFile, $force = false)
    {
        return self::$currentConfig = new Config(self::compile($sourceFile, $force), $sourceFile);
    }

    public static function getCurrentConfig()
    {
        return self::$currentConfig;
    }

    public static function compile($sourceFile, $force = false)
    {
        $compiledFile = ConfigCompiler::compiled_filename($sourceFile);
        if ($force || ConfigCompiler::test($sourceFile, $compiledFile)) {
            $config = ConfigCompiler::parse($sourceFile);
            $config = self::preprocessConfig($config);
            ConfigCompiler::write($compiledFile, $config);
            return $config;
        } else {
            return require $compiledFile;
        }
    }

    public static function preprocessConfig(array $config)
    {
        if (isset($config['data_source'])) {
            $config['data_source'] = self::normalizeNodeConfigArray($config['data_source']);
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

    public static function normalizeNodeConfig(array $config)
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
                $readNodes[] = DSN::updateDSN($c);
            }

            $writeNodes = [];
            foreach ($writeServers as $serverAddress) {
                $c = array_merge($config, []);
                $c['host'] = $serverAddress;
                $writeNodes[] = DSN::updateDSN($c);
            }

            $config['read'] = $readNodes;
            $config['write'] = $writeNodes;
        }

        // build dsn string for PDO
        // if the DSN is not defined, compile the information into dsn if possible.
        if (!isset($config['write']) && !isset($config['read'])) {
            if (!isset($config['dsn'])) {
                $config = DSN::updateDSN($config);
            }
        }
        return $config;
    }


    /**
     * This method is used for compiling config array.
     *
     * @param array PHP array from yaml config file
     */
    public static function normalizeNodeConfigArray(array $dbconfig)
    {
        $newconfig = [];
        foreach ($dbconfig as $nodeId => $config) {
            $newconfig[$nodeId] = self::normalizeNodeConfig($config);
        }
        return $newconfig;
    }


    public static function getInstance()
    {
        static $instance;
        if ($instance) {
            return $instance;
        }
        return $instance = new self();
    }
}
