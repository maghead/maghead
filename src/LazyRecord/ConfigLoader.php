<?php
namespace LazyRecord;
use Exception;

class ConfigLoader
{

    public $config;

    public function load($file)
    {
        return $this->config = require $file;
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

