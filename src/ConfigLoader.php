<?php

namespace Maghead;

use ConfigKit\ConfigCompiler;
use Exception;
use ArrayAccess;
use PDO;
use Maghead\DSN\DSN;
use Symfony\Component\Yaml\Yaml;
use Maghead\Schema\SchemaFinder;

/**
 * Available config key:.
 *
 * schema
 * schema.paths = [ dirpath , path, ... ]
 *
 * seeds = [ script path ]
 * data_source
 * data_source{ ds id } = { 
 *      dsn => ..., 
 *      user => , 
 *      pass => 
 *      connection_options => { ... pdo connection options },
 *      query_options => { 
 *          quote_column => true,
 *          quote_table => true,
 *      }
 * }
 *
 * bootstrap = [ script path, script path ]
 *
 * $config->load();
 * $config->init();
 *
 * $config->initForBuild();  // for build command.
 */
class ConfigLoader
    implements ArrayAccess
{
    /**
     * The stashed config.
     */
    protected $config;

    /**
     * @var array class map
     */
    protected $classMap = array();

    public $symbolFilename = '.lazy.yml';

    public $loaded = false;

    public function loadFromSymbol($force = false)
    {
        if (file_exists($this->symbolFilename)) {
            return $this->load(realpath($this->symbolFilename), $force);
        } elseif (file_exists('.lazy.php')) {
            return $this->load(realpath('.lazy.php'), $force);
        }
    }

    public function writeToSymbol()
    {
        if (!file_exists($this->symbolFilename)) {
            throw new Exception('symbol link '.$this->symbolFilename.' does not exist.');
        }

        $targetFile = readlink($this->symbolFilename);
        if ($targetFile === false || !file_exists($targetFile)) {
            throw new Exception('Missing target config file. incorrect symbol link.');
        }

        $yaml = Yaml::dump($this->config, $inlineLevel = 4, $indentSpaces = 2, $exceptionOnInvalidType = true);
        if (false === file_put_contents($targetFile, "---\n".$yaml)) {
            throw new Exception("YAML config update failed: $targetFile");
        }

        return true;
    }

    /**
     * Load config from array directly.
     *
     * @param array $config
     */
    public function loadFromArray(array $config)
    {
        $this->config = $config;
        $this->loaded = true;
    }

    public function setLoaded($loaded = true)
    {
        $this->loaded = $loaded;
    }

    /**
     * Convert data source config to DSN object.
     *
     * @param array data source config
     *
     * @return Maghead\DSN\DSN
     */
    public static function buildDSNObject(array $config)
    {
        // Build DSN connection string for PDO
        $dsn = new DSN($config['driver']);
        foreach (array('database', 'dbname') as $key) {
            if (isset($config[$key])) {
                $dsn->setAttribute('dbname', $config[$key]);
                break;
            }
        }
        if (isset($config['host'])) {
            $dsn->setAttribute('host', $config['host']);
        }
        if (isset($config['port'])) {
            $dsn->setAttribute('port', $config['port']);
        }

        return $dsn;
    }

    public static function preprocessConfig(array $config)
    {
        if (isset($config['data_source']['nodes'])) {
            $config['data_source']['nodes'] = self::preprocessDataSourceConfig($config['data_source']['nodes']);
        }

        return $config;
    }

    /**
     * This method is used for compiling config array.
     *
     * @param array PHP array from yaml config file
     */
    public static function preprocessDataSourceConfig(array $dbconfig)
    {
        foreach ($dbconfig as &$config) {
            if (!isset($config['driver'])) {
                list($driverType) = explode(':', $config['dsn'], 2);
                $config['driver'] = $driverType;
            }

            // compatible keys for username and password
            if (isset($config['username']) && $config['username']) {
                $config['user'] = $config['username'];
            }

            if (isset($config['password']) && $config['password']) {
                $config['pass'] = $config['password'];
            }
            if (!isset($config['user'])) {
                $config['user'] = null;
            }
            if (!isset($config['pass'])) {
                $config['pass'] = null;
            }

            // build dsn string for PDO
            if (!isset($config['dsn'])) {
                // Build DSN connection string for PDO
                $dsn = self::buildDSNObject($config);
                $config['dsn'] = $dsn->__toString();
            }

            if (!isset($config['query_options'])) {
                $config['query_options'] = array();
            }

            if (!isset($config['connection_options'])) {
                $config['connection_options'] = array();
            }

            if ('mysql' === $config['driver']) {
                $config['connection_options'][ PDO::MYSQL_ATTR_INIT_COMMAND ] = 'SET NAMES utf8';
            }
        }

        return $dbconfig;
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

    /**
     * Load config from the YAML config file...
     *
     * @param string $file
     */
    public function loadFromFile($sourceFile)
    {
        $this->config = self::compile($sourceFile);
        $this->loaded = true;
    }

    /**
     * Load configuration.
     *
     * @param mixed $arg config file.
     */
    public function load($arg, $force = false)
    {
        // should we load config file force ?
        if ($force !== true && $this->loaded === true) {
            throw new Exception('Can not load config. Config is already loaded.');
        }

        if ($arg === null || is_bool($arg)) {
            $arg = $this->symbolFilename;
        }

        if ((is_string($arg) && file_exists($arg)) || $arg === true) {
            $this->loadFromFile($arg);
        } elseif (is_array($arg)) {
            $this->config = self::preprocessConfig($arg);
        } else {
            throw new Exception('unknown config format.');
        }

        // XXX: validate config structure if we are migrating to new major version with incompatible changes
        /*
        if (!isset($this->config['data_source'])) {
            throw new Exception('data_source is missing, please update your config file.');
        }
        */
        $this->loaded = true;
    }

    /**
     * unload config and stash.
     */
    public function unload()
    {
        $this->loaded = false;
        $this->config = null;
    }

    public function setConfigStash(array $stash)
    {
        $this->config = $stash;
    }

    public function getConfigStash()
    {
        return $this->config;
    }

    /**
     * 1. inject config into data source
     * 2. load bootstrap
     * 3. load external schema loader.
     */
    public function init()
    {
        if ($this->loaded) {
            $this->loadDataSources();
        } else {
            throw new Exception('Can not initialize config: Config is not loaded.');
        }
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    public function initForBuild()
    {
        if (!$this->loaded) {
            throw new Exception('Can not initialize config: Config is not loaded.');
        }
        $this->loadDataSources();
        $this->loadBootstrap();
        if (!$this->loadExternalSchemaLoader()) {
            // Load default schema loader
            $paths = $this->getSchemaPaths();
            if (!empty($paths)) {
                $finder = new SchemaFinder($paths);
                $finder->find();
            }
        }
    }

    public static function getInstance()
    {
        static $instance;

        return $instance ? $instance : $instance = new self();
    }

    /**
     * run bootstrap code.
     */
    public function loadBootstrap()
    {
        if (isset($this->config['bootstrap'])) {
            foreach ((array) $this->config['bootstrap'] as $bootstrap) {
                require_once $bootstrap;
            }
        }
    }

    /**
     * load external schema loader.
     */
    protected function loadExternalSchemaLoader()
    {
        if (isset($this->config['schema']['loader'])) {
            require_once $this->config['schema']['loader'];

            return true;
        }

        return false;
    }

    /**
     * load class from php source,
     * to PHP source should return a PHP array.
     */
    protected function loadClassMapFile()
    {
        if (isset($this->config['schema']['loader']) &&
            $file = $this->config['schema']['loader']) {
            return $this->classMap = require $file;
        }

        return array();
    }

    /**
     * load data sources to connection manager.
     */
    protected function loadDataSources()
    {
        // load data source into connection manager
        $manager = ConnectionManager::getInstance();
        $manager->init($this);
    }

    public function getClassMap()
    {
        return $this->classMap ?: $this->loadClassMapFile();
    }

    public function removeDataSource($dataSourceId)
    {
        unset($this->config['data_source']['nodes'][ $dataSourceId ]);
    }

    public function addDataSource($dataSourceId, array $config)
    {
        $this->config['data_source']['nodes'][ $dataSourceId ] = $config;
    }

    /**
     * get all data sources.
     *
     * @return array data source
     */
    public function getDataSources()
    {
        if (isset($this->config['data_source']['nodes'])) {
            return $this->config['data_source']['nodes'];
        }

        return array();
    }

    public function getDataSourceIds()
    {
        if (isset($this->config['data_source']['nodes'])) {
            return array_keys($this->config['data_source']['nodes']);
        }

        return array();
    }

    public function getDefaultDataSource()
    {
        $id = $this->getDefaultDataSourceId();
        if (isset($this->config['data_source']['nodes'][$id])) {
            return $this->config['data_source']['nodes'][$id];
        }
    }

    public function setDefaultDataSourceId($id)
    {
        $this->config['data_source']['default'] = $id;
    }

    public function getDefaultDataSourceId()
    {
        if (isset($this->config['data_source']['default'])) {
            return $this->config['data_source']['default'];
        }

        return 'default';
    }

    public function getSeedScripts()
    {
        if (isset($this->config['seeds'])) {
            return $this->config['seeds'];
        }
    }

    public function getCacheConfig()
    {
        if (isset($this->config['cache'])) {
            return $this->config['cache'];
        }
    }

    // XXX: Provide a simpler config interface and
    // we should use injection container
    public function getCacheInstance()
    {
        static $instance;
        if ($instance) {
            return $instance;
        }

        // XXX:
        $config = $this->getCacheConfig();
        if (isset($config['class'])) {
            $class = $config['class'];
            $instance = new $class($config);

            return $instance;
        }
    }

    /**
     * get data source by data source id.
     *
     * @param string $sourceId
     */
    public function getDataSource($sourceId)
    {
        if ($sourceId === 'default') {
            // If there is a node named 'default', we should use it, otherwise
            // we get the node name from default attribute.
            $sourceId = $this->getDefaultDataSourceId();
        }
        if (isset($this->config['data_source']['nodes'][$sourceId])) {
            return $this->config['data_source']['nodes'][$sourceId];
        }
        throw new Exception("data source $sourceId is not defined.");
    }

    /**
     * get schema config.
     *
     * @return array config
     */
    public function getSchema()
    {
        return isset($this->config['schema']) ?
                     $this->config['schema'] : null;
    }

    /**
     * get schema paths from config.
     *
     * @return array paths
     */
    public function getSchemaPaths()
    {
        return isset($this->config['schema']['paths'])
                    ? $this->config['schema']['paths'] : null;
    }

    public function hasAutoId()
    {
        return isset($this->config['schema']['auto_id']) ? true : false;
    }

    public function getBaseModelClass()
    {
        if (isset($this->config['schema']['base_model'])) {
            return $this->config['schema']['base_model'];
        }

        return '\\Maghead\\BaseModel';
    }

    public function getBaseCollectionClass()
    {
        if (isset($this->config['schema']['base_collection'])) {
            return $this->config['schema']['base_collection'];
        }

        return '\\Maghead\\BaseCollection';
    }

    /******************************
     * Implements interface of ArrayAccess
     ******************************/
    public function offsetGet($offset)
    {
        return $this->config[ $offset ];
    }

    public function offsetSet($offset, $value)
    {
        $this->config[ $offset ] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->config[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }
}
