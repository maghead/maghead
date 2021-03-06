<?php

namespace Maghead\Runtime\Config;

use ArrayObject;
use Exception;
use ReflectionClass;
use Maghead\Sharding\ShardingConfig;
use Maghead\Utils;
use Maghead\Schema\Finder;
use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Column\AutoIncrementPrimaryKeyColumn;
use Maghead\Schema\Column\UUIDPrimaryKeyColumn;

class Config extends ArrayObject
{
    public $file;

    const DEFAULT_BASE_COLLECTION_CLASS = '\\Maghead\\Runtime\\Collection';

    const DEFAULT_BASE_MODEL_CLASS = '\\Maghead\\Runtime\\Model';

    const DEFAULT_AUTO_ID_COLUMN_CLASS = '\\Maghead\\Schema\\Column\\AutoIncrementPrimaryKeyColumn';

    const DEFAULT_MIGRATION_SCRIPT_DIR = 'db/migrations';

    const MASTER_ID = 'master';

    /**
     * The defualt data source ID needs to be "default" and to be resolved in
     * the runtime because these node IDs will be compiled into the schema
     * files.
     */
    const DEFAULT_DATASOURCE_ID = 'master';


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
     * return the external schema finders
     */
    public function loadSchemaFinders(array $namespaceRoots = [], $refObject = null, array $refSubNamespaceNames = [])
    {
        if (!isset($this['schema']['finders'])) {
            return [];
        }

        $configs = array_map(function($config) {
            if (is_string($config)) {
                return [ 'name' => $config ];
            }
            return $config;
        }, (array) $this['schema']['finders']);

        $finders = [];

        foreach ($configs as $config) {
            $name = $config['name'];

            array_push($namespaceRoots, Finder::class);
            array_push($refSubNamespaceNames, "Schema\\Finder");

            $class = Utils::resolveClass($name, $namespaceRoots, $refObject, $refSubNamespaceNames);

            if (!$class) {
                throw new Exception("Finder class '$name' can't be loaded.");
            }

            $reflClass = new ReflectionClass($class);
            if (isset($config['args'])) {
                $finders[] = $reflClass->newInstanceArgs($config['args']);
            } else {
                $finders[] = $reflClass->newInstance();
            }
        }
        return $finders;
    }

    public function createPrimaryKeyColumn(DeclareSchema $schema, $columnName)
    {
        $column = null;
        $columnClass = null;
        if ($this->hasAutoIdConfig()) {
            if ($cls = $this->getAutoIdColumnClass()) {
                $columnClass = $cls;
            }
            if ($n = $this->getAutoIdColumnName()) {
                $columnName = $n;
            }
        }

        if ($columnClass) {
            $refClass = new ReflectionClass($columnClass);
            return $refClass->newInstanceArgs([$schema, $columnName]);
        }

        return new AutoIncrementPrimaryKeyColumn($schema, $columnName, 'integer');
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
