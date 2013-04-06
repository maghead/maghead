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
    protected $_columnNamesWithVirutal;


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
     * Inject schema array data into runtime schema object
     *
     * @param array $schemaArray
     */
    public function import($schemaArray)
    {
        $this->columnData  = $schemaArray['column_data']; /* contains column names => column attribute array */
        $this->columnNames = $schemaArray['column_names']; /* column names array */
        $this->primaryKey = $schemaArray['primary_key'];
        $this->table = $schemaArray['table'];
        $this->modelClass = $schemaArray['model_class'];
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
            return array_keys($this->columns);
        }

        // We can build cached virutal column data from schema proxy
        // do filtering
        if ( $this->_columnNamesWithVirutal ) {
            return $this->_columnNamesWithVirutal;
        }

        foreach( $this->columns as $name => $column ) {
            // skip virtual columns
            if ( $column->virtual ) {
                continue;
            }
            $names[] = $name;
        }
        return $this->_columnNamesWithVirutal = $names;
    }

    public function getColumns($includeVirtual = false) 
    {
        if( $includeVirtual ) {
            return $this->columns;
        }

        $columns = array();
        foreach( $this->columns as $name => $column ) {
            // skip virtal columns
            if ( $column->virtual ) {
                continue;
            }
            $columns[ $name ] = $column;
        }
        return $columns;
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
        return $this->table;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getModelClass()
    {
        return $this->modelClass;
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
