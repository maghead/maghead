<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\RuntimeColumn;
use Exception;

class RuntimeSchema extends SchemaBase
    implements SchemaDataInterface
{
    public $modelClass;

    public $collectionClass;

    // columns array
    public $columns = array();

    public function __construct() {
        // build RuntimeColumn objects
        foreach( $this->columnData as $name => $columnMeta ) {
            $this->columns[ $name ] = new RuntimeColumn( $name , $columnMeta['attributes'] );
        }
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
        if( isset($this->columns[$name]) ) {
            return true;
        }
    }


    public function getColumn($name)
    {
        if( isset($this->columns[ $name ]) ) {
            return $this->columns[ $name ];
        }
        return null;
    }

    public function getColumnNames($includeVirtual = false)
    {
        $names = array();
        foreach( $this->columns as $name => $column ) {
            if( ! $includeVirtual && $column->virtual )
                continue;
            $names[] = $name;
        }
        return $names;
    }

    public function getColumns($includeVirtual = false) 
    {
        if( $includeVirtual ) {
            return $this->columns;
        }

        $columns = array();
        foreach( $this->columns as $name => $column ) {
            // skip virtal columns
            if( $column->virtual )
                continue;
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
