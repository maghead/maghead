<?php

namespace Maghead\Runtime\Config;

use ArrayObject;
use Exception;
use Maghead\Sharding\ShardingConfig;

class Config extends ArrayObject
{
    public $file;

    const DEFAULT_BASE_COLLECTION_CLASS = '\\Maghead\\Runtime\\BaseCollection';

    const DEFAULT_BASE_MODEL_CLASS = '\\Maghead\\Runtime\\BaseModel';

    const DEFAULT_AUTO_ID_COLUMN_CLASS = '\\Maghead\\Schema\\Column\\AutoIncrementPrimaryKeyColumn';

    const MASTER_ID = 'master';

    public function __construct(array $stash, $file = null)
    {
        parent::__construct($stash);
        $this->file = $file;
    }

    public function getAppId()
    {
        if (isset($this['appId'])) {
            return $this['appId'];
        }
    }

    /**
     * return the config server uri if any
     *
     * @return string
     */
    public function getConfigServerUrl()
    {
        if (isset($this['configServer'])) {
            return $this['configServer'];
        }
    }


    /**
     * return the bootstrap script path
     *
     * @return string 
     */
    public function getBootstrapScript()
    {
        if (isset($this['cli']['bootstrap'])) {
            return $this['cli']['bootstrap'];
        }
    }

    /**
     * load external schema loader.
     */
    public function getExternalSchemaLoader()
    {
        if (isset($this['schema']['loader'])) {
            return $this['schema']['loader'];
        }
    }

    public function removeDatabase($dataSourceId)
    {
        unset($this['databases'][ $dataSourceId ]);
    }

    public function addDatabase($dataSourceId, array $config)
    {
        $this['databases'][ $dataSourceId ] = $config;
    }

    /**
     * get all data sources.
     *
     * @return array data source
     */
    public function getDataSources()
    {
        if (isset($this['databases'])) {
            return $this['databases'];
        }

        return array();
    }

    public function getMasterDataSource()
    {
        $id = $this->getMasterDataSourceId();

        if (isset($this['databases'][$id])) {
            return $this['databases'][$id];
        }
    }

    public function getMasterDataSourceId()
    {
        return self::MASTER_ID;
    }


    /**
     * load seed classes and return the seed objects
     */
    public function loadSeedScripts()
    {
        if (!isset($this['seeds'])) {
            return [];
        }
        $seeds = [];
        foreach ($this['seeds'] as $seed) {
            $seed = str_replace('::', '\\', $seed);
            if (class_exists($seed, true)) {
                $seeds[] = new $seed;
            }
        }
        return $seeds;
    }

    public function setShardingConfig(array $config)
    {
        $this['sharding'] = $config;
    }

    /**
     * Return the sharding config.
     */
    public function getShardingConfig()
    {
        if (isset($this['sharding'])) {
            return new ShardingConfig($this['sharding']);
        }
    }

    public function getCacheConfig()
    {
        if (isset($this['cache'])) {
            return $this['cache'];
        }
    }

    public function getInstances()
    {
        return $this['instance'];
    }

    /**
     * get data source by data source id.
     *
     * @param string $sourceId
     */
    public function getDataSource($sourceId)
    {
        if (isset($this['databases'][$sourceId])) {
            return $this['databases'][$sourceId];
        }
        throw new Exception("database $sourceId is not defined.");
    }

    /**
     * get schema config.
     *
     * @return array config
     */
    public function getSchema()
    {
        return isset($this['schema']) ?
                     $this['schema'] : null;
    }

    /**
     * get schema paths from config.
     *
     * @return array paths
     */
    public function getSchemaPaths()
    {
        return isset($this['schema']['paths'])
                    ? $this['schema']['paths'] : null;
    }

    public function setAutoId($enabled = true)
    {
        $this['schema']['auto_id'] = $enabled;
    }

    public function hasAutoId()
    {
        return isset($this['schema']['auto_id']);
    }


    public function getAutoIdColumnName()
    {
        if (is_array($this['schema']['auto_id'])) {
            if (isset($this['schema']['auto_id']['name'])) {
                return $this['schema']['auto_id']['name'];
            }
        }
        return 'id';
    }

    public function hasAutoIdConfig()
    {
        return is_array($this['schema']['auto_id']);
    }

    // TODO: column classes alias should be defined here.
    // TODO: implement the column object factory
    // TODO: dynamically resolve the column classes
    public function getAutoIdColumnClass()
    {
        if (is_array($this['schema']['auto_id'])) {
            if (isset($this['schema']['auto_id']['class'])) {
                return $this['schema']['auto_id']['class'];
            }
        }
        // Alternative class '\Maghead\Schema\Column\UUIDPrimaryKeyColumn';
        return self::DEFAULT_AUTO_ID_COLUMN_CLASS;
    }

    public function getAutoIdColumnParams()
    {
        if (is_array($this['schema']['auto_id'])) {
            if (isset($this['schema']['auto_id']['params'])) {
                return $this['schema']['auto_id']['params'];
            }
        }
        // Alternative class '\Maghead\Schema\Column\UUIDPrimaryKeyColumn';
        return self::DEFAULT_AUTO_ID_COLUMN_CLASS;
    }

    public function getBaseModelClass()
    {
        if (isset($this['schema']['base_model'])) {
            return $this['schema']['base_model'];
        }

        return self::DEFAULT_BASE_MODEL_CLASS;
    }

    public function getBaseCollectionClass()
    {
        if (isset($this['schema']['base_collection'])) {
            return $this['schema']['base_collection'];
        }

        return self::DEFAULT_BASE_COLLECTION_CLASS;
    }
}
