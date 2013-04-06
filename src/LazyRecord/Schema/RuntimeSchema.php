<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\RuntimeColumn;
use Exception;
use IteratorAggregate;
use ArrayIterator;

class RuntimeSchema extends SchemaBase
    implements SchemaDataInterface, IteratorAggregate
{
    public $modelClass;

    public $collectionClass;

    // columns array
    public $columnData = array();

    /**
     * @var array cached columns including virutal columns
     */
    protected $_columnNamesExcludeVirutal;


    public function __construct() {
        // build RuntimeColumn objects
        foreach( $this->columnData as $name => $columnMeta ) 
        {
            $this->columns[ $name ] = new RuntimeColumn( $name , $columnMeta['attributes'] );
        }
    }

    /**
     * For iterating attributes
     */
    public function getIterator()
    {
        return new ArrayIterator($this->columns);
    }


    /**
     * Inject schema array data into runtime schema object,
     * This is much like __set_state method
     *
     * @param array $schemaArray
     */
    public function import($schemaArray)
    {
        $this->columnData  = $schemaArray['column_data']; /* contains column names => column attribute array */
        $this->columnNames = $schemaArray['column_names']; /* column names array */
        $this->primaryKey = $schemaArray['primary_key'];
        $this->table      = $schemaArray['table'];
        $this->label      = $schemaArray['label'];
        $this->modelClass = $schemaArray['model_class'];
    }

    public static function __set_state($array) 
    {
        $schema = new self;
        $schema->import($array);
        return $schema;
    }



    public function hasColumn($name)
    {
        return isset($this->columns[$name]);
    }


    public function getColumn($name)
    {
        if( isset($this->columns[ $name ]) ) {
            return $this->columns[ $name ];
        }
    }

    public function getColumnNames($includeVirtual = false)
    {
        if ( $includeVirtual ) {
            return static::$column_names_include_virtual;
        } else {
            return static::$column_names;
        }
    }

    public function getColumns($includeVirtual = false) 
    {
        // returns all columns
        if( $includeVirtual ) {
            return $this->columns;
        }
        $names = array_fill_keys(static::$column_names,1);
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
        return static::table;
    }

    public function getLabel()
    {
        return static::label;
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
        return static::model_class;
    }

    public function getCollectionClass()
    {
        return static::collection_class;
    }

    public function newModel()
    {
        return new $this->modelClass;
    }

    public function newCollection()
    {
        return new $this->collectionClass;
    }


}
