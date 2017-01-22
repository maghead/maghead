<?php

namespace Maghead\Schema;

use IteratorAggregate;
use ArrayIterator;

class RuntimeSchema extends SchemaBase
    implements SchemaInterface, IteratorAggregate
{
    public $modelClass;

    public $collectionClass;

    // columns array
    public $columnData = array();

    /**
     * @var array cached columns including virutal columns
     */
    protected $_columnNamesExcludeVirutal;

    public function __construct()
    {
        // build RuntimeColumn objects
        /*
        foreach ($this->columnData as $name => $columnMeta) {
            $this->columns[ $name ] = new RuntimeColumn($name,$columnMeta['attributes']);
        }
        */
    }

    /**
     * For iterating attributes.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->columns);
    }

    public static function __set_state($array)
    {
        $schema = new self();
        $schema->columnData = $array['column_data']; /* contains column names => column attribute array */
        $schema->columnNames = $array['column_names']; /* column names array */
        $schema->label = $array['label'];
        $schema->modelClass = $array['model_class'];

        return $schema;
    }

    public function hasColumn($name)
    {
        return isset($this->columns[$name]);
    }

    public function getColumn($name)
    {
        if (isset($this->columns[ $name ])) {
            return $this->columns[ $name ];
        }
    }

    public function getColumnNames($includeVirtual = false)
    {
        if ($includeVirtual) {
            return $this->columnNamesIncludeVirtual;
        } else {
            return $this->columnNames;
        }
    }

    public function getRenderableColumnNames()
    {
        return array_map(function ($column) { return $column->name; }, array_filter($this->columns, function ($column) {
            return $column->renderable !== false;
        }));
    }

    public function getColumns($includeVirtual = false)
    {
        // returns all columns
        if ($includeVirtual) {
            return $this->columns;
        }
        $names = array_fill_keys($this->columnNames, 1);

        return array_intersect_key($this->columns, $names);
    }

    public function getReadSourceId()
    {
        return $this->readSourceId;
    }

    public function getWriteSourceId()
    {
        return $this->writeSourceId;
    }

    public function getTable()
    {
        return static::TABLE;
    }

    public function getLabel()
    {
        return static::LABEL;
    }

    // Class related methods

    public function getModelName()
    {
        return static::model_name;
    }

    public function getNamespace()
    {
        return static::model_namespace;
    }

    public function getModelClass()
    {
        return static::MODEL_CLASS;
    }

    public function getCollectionClass()
    {
        return static::COLLECTION_CLASS;
    }

    public function newModel()
    {
        $class = static::MODEL_CLASS;

        return new $class();
    }

    public function newCollection()
    {
        $class = static::COLLECTION_CLASS;

        return new $class();
    }
}
