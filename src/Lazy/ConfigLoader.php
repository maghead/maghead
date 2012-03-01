<?php
namespace Lazy;
use Exception;
use ArrayAccess;

/**
 * Available config key:
 *
 * schema
 * schema.paths = [ dirpath , path, ... ]
 *
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
 */
class ConfigLoader
    implements ArrayAccess
{
    public $config;

    public $symbolFilename = '.lazy.php';

    public $classMap;

    public function getInstance()
    {
        static $instance;
        return $instance ?: $instance = new static;
    }

    /**
     * load configuration file
     *
     * @param string $file config file.
     */
    public function loadConfig($file = null)
    {
        if( ! $file )
            $file = $this->symbolFilename;

        if( ! file_exists($file) ) 
            throw new Exception("$file does not exist, please run build-conf command to build config file for PHP.");

        $this->config = require $file;
    }

    public function init()
    {
        $this->loadDataSources();
        $this->loadBootstrap();
        $this->loadExternalSchemaLoader();
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
        if( isset($this->config['schema']['class_map_file']) && 
            $file = $this->config['schema']['class_map_file'] ) 
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

