<?php
namespace Lazy;
use Exception;

class ConfigLoader
{
    public $config;

    public function load($file)
    {
        $this->config = require $file;

        // load data source into connection manager
        $manager = ConnectionManager::getInstance();
        foreach( $this->getDataSources() as $sourceId => $ds ) {
            $manager->addDataSource( $sourceId , $ds );
        }
    }

    public function getDataSources()
    {
        return $this->config['data_sources'];
    }

    public function getDataSource($sourceId)
    {
        if( isset( $this->config['data_sources'][$sourceId] ) )
            return $this->config['data_sources'][$sourceId];

        throw new Exception("data source $sourceId is not defined.");
    }

    public function getSchema()
    {
        return $this->config['schema'];
    }

    public function getSchemaPaths()
    {
        return $this->config['schema']['paths'];
    }


}

