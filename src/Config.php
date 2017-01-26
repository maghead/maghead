<?php

namespace Maghead;

class Config
{
    protected $stash = [];

    protected $classMap = [];

    public function __construct(array $stash)
    {
        $this->stash = $stash;
    }

    /**
     * run bootstrap code.
     */
    public function getBootstrap()
    {
        if (isset($this->stash['bootstrap'])) {
            return (array) $this->stash['bootstrap'];
        }
    }

    /**
     * load external schema loader.
     */
    public function getExternalSchemaLoader()
    {
        if (isset($this->stash['schema']['loader'])) {
            return $this->stash['schema']['loader'];
        }
    }

    public function getClassMap()
    {
        return $this->classMap;
    }

    public function removeDataSource($dataSourceId)
    {
        unset($this->stash['data_source']['nodes'][ $dataSourceId ]);
    }

    public function addDataSource($dataSourceId, array $config)
    {
        $this->stash['data_source']['nodes'][ $dataSourceId ] = $config;
    }

    /**
     * get all data sources.
     *
     * @return array data source
     */
    public function getDataSources()
    {
        if (isset($this->stash['data_source']['nodes'])) {
            return $this->stash['data_source']['nodes'];
        }

        return array();
    }

    public function getDataSourceIds()
    {
        if (isset($this->stash['data_source']['nodes'])) {
            return array_keys($this->stash['data_source']['nodes']);
        }

        return array();
    }

    public function getDefaultDataSource()
    {
        $id = $this->getDefaultDataSourceId();
        if (isset($this->stash['data_source']['nodes'][$id])) {
            return $this->stash['data_source']['nodes'][$id];
        }
    }

    public function setDefaultDataSourceId($id)
    {
        $this->stash['data_source']['default'] = $id;
    }

    public function getDefaultDataSourceId()
    {
        if (isset($this->stash['data_source']['default'])) {
            return $this->stash['data_source']['default'];
        }

        return 'default';
    }

    public function getSeedScripts()
    {
        if (isset($this->stash['seeds'])) {
            return $this->stash['seeds'];
        }
    }

    public function getCacheConfig()
    {
        if (isset($this->stash['cache'])) {
            return $this->stash['cache'];
        }
    }

    /**
     * get data source by data source id.
     *
     * @param string $sourceId
     */
    public function getDataSource($sourceId)
    {
        if (isset($this->stash['data_source']['nodes'][$sourceId])) {
            return $this->stash['data_source']['nodes'][$sourceId];
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
        return isset($this->stash['schema']) ?
                     $this->stash['schema'] : null;
    }

    /**
     * get schema paths from config.
     *
     * @return array paths
     */
    public function getSchemaPaths()
    {
        return isset($this->stash['schema']['paths'])
                    ? $this->stash['schema']['paths'] : null;
    }

    public function hasAutoId()
    {
        return isset($this->stash['schema']['auto_id']) ? true : false;
    }

    public function getBaseModelClass()
    {
        if (isset($this->stash['schema']['base_model'])) {
            return $this->stash['schema']['base_model'];
        }

        return '\\Maghead\\BaseModel';
    }

    public function getBaseCollectionClass()
    {
        if (isset($this->stash['schema']['base_collection'])) {
            return $this->stash['schema']['base_collection'];
        }

        return '\\Maghead\\BaseCollection';
    }

    public function getStash()
    {
        return $this->stash;
    }

    public function setStash(array $config)
    {
        $this->stash = $config;
    }
}
