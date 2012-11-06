<?php
namespace LazyRecord;
use ConfigKit\ConfigCompiler;
use Exception;
use ArrayAccess;

/**
 * Available config key:
 *
 * schema
 * schema.paths = [ dirpath , path, ... ]
 *
 * seeds = [ script path ]
 * data_sources
 * data_sources{ ds id } = { 
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
    public $config;

    public $symbolFilename = '.lazy.yml';

    public $classMap;

    public $loaded = false;

    public function loadFromSymbol($force = false)
    {
        if( file_exists($this->symbolFilename) ) {
            return $this->load( $this->symbolFilename, $force );
        }
        elseif( file_exists('.lazy.php') ) {
            return $this->load('.lazy.php', $force );
        }
        else {
            throw new Exception("lazyrecord config symbol .lazy.php or .lazy.yml is not found.");
        }
    }

    /**
     * Load configuration file
     *
     * @param string $file config file.
     */
    public function load($file, $force = false)
    {
        // should we load config file force ?
        if( $force !== true && $this->loaded === true ) {
            throw new Exception("Can not load $file. Config is already loaded.");
        }

        if( $file === null || is_bool($file) )
            $file = $this->symbolFilename;

        if( (is_string($file) && file_exists($file)) || $file === true ) {
            $this->config = ConfigCompiler::load($file);
        }
        elseif( is_array($file) ) {
            $this->config = $file;
        }
        else {
            throw new Exception("unknown config format.");
        }
        $this->loaded = true;
    }

    /**
     * unload config and stash
     */
    public function unload()
    {
        $this->loaded = false;
        $this->config = null;
    }


    /**
     * 1. inject config into data source
     * 2. load bootstrap
     * 3. load external schema loader.
     */
    public function init()
    {
        if( $this->loaded ) {
            $this->loadDataSources();
        } else {
            throw new Exception('Can not initialize config: Config is not loaded.');
        }
    }

    public function initForBuild()
    {
        if( $this->loaded ) {
            $this->loadDataSources();
            $this->loadBootstrap();
            $this->loadExternalSchemaLoader();
        } else {
            throw new Exception('Can not initialize config: Config is not loaded.');
        }
    }

    static function getInstance()
    {
        static $instance;
        return $instance ? $instance : $instance = new self;
    }


    /**
     * run bootstrap code
     */
    public function loadBootstrap()
    {
        if( isset($this->config['bootstrap'] ) ) {
            foreach( (array) $this->config['bootstrap'] as $bootstrap ) {
                require_once $bootstrap;
            }
        }
    }


    /**
     * load external schema loader
     */
    public function loadExternalSchemaLoader()
    {
        if( isset($this->config['schema']['loader']) ) {
            require_once $this->config['schema']['loader'];
        }
    }


    /**
     * load class from php source,
     * to PHP source should return a PHP array.
     */
    public function loadClassMapFile() 
    {
        if( isset($this->config['schema']['loader']) && 
            $file = $this->config['schema']['loader'] ) 
        {
            return $this->classMap = require $file;
        }
        return array();
    }


    /**
     * load data sources to connection manager
     */
    public function loadDataSources()
    {
        // load data source into connection manager
        $manager = ConnectionManager::getInstance();
        foreach( $this->getDataSources() as $sourceId => $ds ) {
            $manager->addDataSource( $sourceId , $ds );
        }
    }


    public function getClassMap()
    {
        return $this->classMap ?: $this->loadClassMapFile();
    }




    /**
     * get all data sources
     *
     * @return array data source
     */
    public function getDataSources()
    {
        return $this->config['data_sources'];
    }

    public function getSeedScripts() {
        if( isset($this->config['seeds']) ) {
            return $this->config['seeds'];
        }
    }

    public function getCacheConfig()
    {
        if( isset($this->config['cache']) )
            return $this->config['cache'];
    }


    // XXX: provide a simpler config interface and 
    // we should use injection container
    public function getCacheInstance()
    {
        static $instance;
        if ( $instance )
            return $instance;
        $config = $this->getCacheConfig();
        if( isset($config['class']) ) {
            $class = $config['class'];
            $instance = new $class( $config );
            return $instance;
        }
    }

    /**
     * get data source by data source id
     *
     * @param string $sourceId
     */
    public function getDataSource($sourceId)
    {
        if( isset( $this->config['data_sources'][$sourceId] ) )
            return $this->config['data_sources'][$sourceId];

        throw new Exception("data source $sourceId is not defined.");
    }


    /**
     * get schema config
     *
     * @return array config
     */
    public function getSchema()
    {
        return isset($this->config['schema']) ?
                     $this->config['schema'] : null;
    }


    /**
     * get schema paths from config
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
        return @$this->config['schema']['base_model'] ?: '\LazyRecord\BaseModel';
    }


    public function getBaseCollectionClass() 
    {
        return @$this->config['schema']['base_collection'] ?: '\LazyRecord\BaseCollection';
    }

    /******************************
     * Implements interface of ArrayAccess
     ******************************/
    public function offsetGet($offset)
    {
        return $this->config[ $offset ];
    }

    public function offsetSet($offset,$value)
    {
        $this->config[ $offset ] = $value;
    }

    public function offsetExists ($offset)
    {
        return isset($this->config[$offset]);
    }
    
    public function offsetUnset($offset) 
    {
        unset($this->config[$offset]);
    }



}

