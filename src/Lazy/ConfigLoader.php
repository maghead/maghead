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
 *
 */
class ConfigLoader
    implements ArrayAccess
{
    public $config;


    /**
     * load configuration file
     *
     * @param string $file config file.
     */
    public function loadConfig($file)
    {
        $this->config = require $file;
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
        return $this->config['schema'];
    }


    /**
     * get schema paths from config
     *
     * @return array paths
     */
    public function getSchemaPaths()
    {
        return $this->config['schema']['paths'];
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

