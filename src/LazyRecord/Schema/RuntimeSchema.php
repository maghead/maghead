<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\RuntimeColumn;
use Exception;

class RuntimeSchema extends SchemaBase
{


    public $modelClass;

    public $collectionClass;

    public $columnCached = array();

    public function import($schemaArray)
    {
        $this->columns = $schemaArray['columns'];
        $this->columnNames = $schemaArray['column_names'];
        $this->primaryKey = $schemaArray['primary_key'];
        $this->table = $schemaArray['table'];
        $this->modelClass = $schemaArray['model_class'];
    }


    public function hasColumn($name)
    {
        if( isset($this->columnCached[ $name ]) || isset($this->columns[$name] ) )  {
            return true;
        }
    }

    public function getColumn($name)
    {
        if( isset($this->columnCached[ $name ]) )  {
            return $this->columnCached[ $name ];
        } elseif( isset($this->columns[$name]) ) {
            return $this->columnCached[ $name ] = new RuntimeColumn( $name , $this->columns[$name]['attributes'] );
        }
        return null;
    }

    public function getColumnNames($includeVirtual = false)
    {
        $names = array();
        foreach( $this->columns as $name => $data ) {
            if( ! $includeVirtual && isset($data['attributes']['virtual']) )
                continue;
            $names[] = $name;
        }
        return $names;
    }

    public function getColumns($includeVirtual = false) 
    {
        $columns = array();
        foreach( $this->columns as $name => $data ) {
            $column = $this->getColumn( $name );
            if( ! $includeVirtual && $column->virtual )
                continue;
            $columns[ $name ] = $column;
        }
        return $columns;
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
