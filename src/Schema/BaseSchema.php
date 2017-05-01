<?php

namespace Maghead\Schema;

use RuntimeException;
use InvalidArgumentException;
use Exception;
use Maghead\Exception\TableNameConversionException;
use Doctrine\Common\Inflector\Inflector;

abstract class BaseSchema
{
    const PRIMARY_KEY = null;

    const GLOBAL_PRIMARY_KEY = null;

    const LOCAL_PRIMARY_KEY = null;

    const TABLE = null;

    public $label;

    public $columns = [];

    public $columnNames = [];

    public $relations = [];

    public $readSourceId = 'master';

    public $writeSourceId = 'master';

    public $mixinSchemaClasses = [];

    public $mixinSchemas = [];

    public $seeds = [];

    protected $_namespace;

    protected $_modelName;

    abstract public function getModelClass();

    public function getModelName()
    {
        if ($this->_modelName) {
            return $this->_modelName;
        }

        $class = $this->getModelClass();
        $p = strrpos($class, '\\');
        if ($p === false) {
            return $class;
        }

        return $this->_modelName = substr($class, $p + 1);
    }

    public function addMixinSchemaClass($class)
    {
        $this->mixinSchemaClasses[] = $class;
    }

    public function hasMixinSchemaClass($class)
    {
        return in_array($class, $this->mixinSchemaClasses);
    }

    public function getMixinSchemaClasses()
    {
        return $this->mixinSchemaClasses;
    }

    public function getSeedClasses()
    {
        return $this->seeds;
    }

    // Class name related methods
    public function getBaseModelClass()
    {
        return $this->getModelClass().'Base';
    }

    public function getBaseModelName()
    {
        return $this->getModelName().'Base';
    }

    public function getRepoClass()
    {
        return $this->getModelClass().'Repo';
    }

    public function getBaseRepoClass()
    {
        return $this->getModelClass().'BaseRepo';
    }

    public function getCollectionClass()
    {
        return $this->getModelClass().'Collection';
    }

    public function getBaseCollectionClass()
    {
        return $this->getModelClass().'CollectionBase';
    }

    public function getSchemaProxyClass()
    {
        return $this->getModelClass().'SchemaProxy';
    }

    public function newRepo($write = null, $read = null)
    {
        $class = $this->getModelClass();
        return $class::repo($write, $read);
    }

    public function newModel()
    {
        $class = $this->getModelClass();

        return new $class();
    }

    public function newCollection()
    {
        $class = $this->getCollectionClass();

        return new $class();
    }

    /**
     * Get class namespace.
     */
    public function getNamespace()
    {
        if ($this->_namespace) {
            return $this->_namespace;
        }

        $class = $this->getModelClass();
        $p = strrpos($class, '\\');
        if ($p === false) {
            return $class;
        }

        return $this->_namespace = substr($class, 0, $p);
    }

    /**
     * Get a relationship data by a relation identity.
     *
     * @param string $relationId
     */
    public function getRelation($relationId)
    {
        if (isset($this->relations[ $relationId ])) {
            return $this->relations[ $relationId ];
        }
    }

    /**
     * Get relationship data.
     */
    public function getRelations()
    {
        return $this->relations;
    }





    /**
     * For schema class, get its reference schema classes recursively.
     *
     * @param bool $recursive
     */
    public function getReferenceSchemas($recursive = true)
    {
        $schemas = [];
        foreach ($this->relations as $relKey => $rel) {
            if (!isset($rel['foreign_schema'])) {
                continue;
            }

            $class = ltrim($rel['foreign_schema'], '\\');
            if (isset($schemas[$class])) {
                continue;
            }
            if (!class_exists($class, true)) {
                throw new RuntimeException("Foreign schema class '$class' not found in schema {$this}.");
            }

            if (is_a($class, 'Maghead\\Runtime\\BaseModel', true)) {
                // bless model class to schema object.
                if (!method_exists($class, 'schema')) {
                    throw new Exception(get_class($this).": You need to define schema method in $class class.");
                }
                $schemas[ $class ] = 1;
                $model = new $class();
                $schema = new DynamicSchemaDeclare($model);
                if ($recursive) {
                    $schemas = array_merge($schemas, $schema->getReferenceSchemas(false));
                }
            } elseif (is_subclass_of($class, 'Maghead\\Schema\\DeclareSchema', true)) {
                $schemas[ $class ] = 1;
                $fs = new $class();
                if ($recursive) {
                    $schemas = array_merge($schemas, $fs->getReferenceSchemas(false));
                }
            } else {
                throw new InvalidArgumentException("Foreign schema class '$class' is not a SchemaDeclare class");
            }
        }

        return $schemas;
    }


    public static function convertClassToTableName($class)
    {
        if (!preg_match('/(\w+?)(?:Model)?$/', $class, $reg)) {
            throw new TableNameConversionException("Table name convert error: $class", $class);
        }
        if (count($reg) < 2) {
            throw new Exception("Can not parse model name: $class");
        }

        // Convert BlahBlah to blah_blah
        $table = $reg[1];
        $table = Inflector::tableize($table);

        return Inflector::pluralize($table);
    }
}
