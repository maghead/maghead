<?php
namespace LazyRecord;
use LazyRecord\SchemaDeclare\Column;
use Exception;

abstract class SchemaDeclare
{
    const has_one = 1;
    const has_many = 2;
    const many_to_many = 3;
    const belongs_to = 4;

    public $relations = array();

    // public $accessors = array();
    public $columns = array();

    public $columnNames = array();

    public $primaryKey;

    public $table;

    public $mixins = array();

    // XXX: 
    public $readSourceId;

    public $writeSourceId;

    abstract function schema();

    public function __construct()
    {
        $this->build();
    }

    public function build()
    {
        $this->schema();

        /* find primary key */
        foreach( $this->columns as $name => $column ) {
            if( $column->primary )
                $this->primaryKey = $name;
        }

        /*
        foreach( $this->mixins as $mixinClass ) {
        }
        */
    }

    public function export()
    {
        $columnArray = array();
        foreach( $this->columns as $name => $column ) {
            $columnArray[ $name ] = $column->export();
        }

        return array(
            'table' => $this->getTable(),
            'columns' => $columnArray,
            'column_names' => $this->columnNames,
            'primary_key' => $this->primaryKey,
            'model_class' => $this->getModelClass(),
            'relations' => $this->relations,
        );
    }

    public function dump()
    {
        return var_export( $this->export() , true );
    }

    protected function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function mixin($class)
    {
        $this->mixins[] = $class;

        $mixin = new $class;

        /* merge columns into self */
        $this->columns = array_merge( $mixin->columns, $this->columns );
        $this->relations = array_merge( $mixin->relations, $this->relations );
    }

    public function getTable() 
    {
        return $this->table 
            ? $this->table 
            : $this->_classnameToTable();
    }




    /**
     * classname methods
     */
    public function getModelClass()
    {
        static $class;
        if( $class )
            return $class;

        if( ( $p = strrpos( $class = get_class($this) , 'Schema' ) ) !== false ) {
            return $class = substr( $class , 0 , $p );
        }
        throw new Exception('Can not get model class from ' . $class );
    }

    public function getModelName()
    {
        $p = explode('\\',$this->getModelClass());
        return end($p);
    }

    public function getBaseModelClass()
    {
        return $this->getModelClass() . 'Base';
    }

    public function getBaseModelName()
    {
        return $this->getModelName() . 'Base';
    }

    public function getCollectionClass()
    {
        return $this->getModelClass() . 'Collection';
    }

    public function getBaseCollectionClass()
    {
        return $this->getModelClass() . 'CollectionBase';
    }

    public function getSchemaProxyClass()
    {
        return $this->getModelClass() . 'SchemaProxy';
    }

    public function getNamespace()
    {
        $refl = new \ReflectionObject($this);
        return $refl->getNamespaceName();
    }

    public function getClass()
    {
        return get_class($this);
    }

    public function getShortName()
    {
        $refl = new \ReflectionObject($this);
        return $refl->getShortName();
    }

    public function setWriteSource($sourceId)
    {
        $this->writeSourceId = $sourceId;
    }

    public function setReadSource($sourceId)
    {
        $this->readSourceId = $sourceId;
    }

    protected function _classnameToTable() 
    {
        $class = $this->getModelClass();

        /**
         * If we got Yasumi\Model\UserModel, we have to strip. 
         */
        if( preg_match( '/(\w+?)(?:Model)?$/', $class ,$reg) ) {
            $table = @$reg[1];
            if( ! $table )
                throw new Exception( "Table name error: $class" );

            /* convert BlahBlah to blah_blah */
            $table =  strtolower( preg_replace( 
                '/(\B[A-Z])/e' , 
                "'_'.strtolower('$1')" , 
                $table ) );

            $inf = Inflector::getInstance();
            return $inf->pluralize( $table );
        } else { 
            throw new Exception('Table name convert error');
        }
    }

    protected function column($name)
    {
        if( isset($this->columns[$name]) ) {
            throw new Exception("column $name of ". get_class($this) . " is already defined.");
        }
        $this->columnNames[] = $name;
        return $this->columns[ $name ] = new Column( $name );
    }


	public function getRelation($relationId)
	{
        if( ! isset($this->relations[ $relationId ]) ) {
            throw new Exception("Relation $relationId is not defined.");
        }
        return $this->relations[ $relationId ];
	}



    /**
     * define foreign key reference
     */
    protected function belongsTo($foreignClass,$foreignColumn)
    {
        $this->relations[ 'belongs_to:' . $foreignClass ] = array(
            'type' => self::belongs_to,
            'foreign' => array(
                'schema' => $foreignClass,
                'column' => $foreignColumn,
            )
        );
    }


    protected function hasOne($accessor,$selfColumn,$foreignClass,$foreignColumn = null)
    {
        // foreignColumn is default to foreignClass.primary key

        // $this->accessors[ $accessor ] = array( );
        $selfClass = $this->getModelClass();
        $this->relations[ $accessor ] = array(
            'type'           => self::has_one,
            'self'           => array(
                'column'  => $selfColumn,
                'schema'  => $selfClass,
            ),
            'foreign' => array(
                'column' => $foreignColumn,
                'schema' => $foreignClass,
            ),
        );
    }

    protected function hasMany($accessor,$foreignClass,$foreignColumn,$selfColumn)
    {
        $modelClass = $this->getModelClass();
        $this->relations[ $accessor ] = array(
            'type'           => self::has_many,
            'self' => array(
                'column'           => $selfColumn,
                'schema'           => $modelClass,
            ),
            'foreign'  => array( 
                'schema' => $foreignClass,
                'column' => $foreignColumn
            )
        );
    }

    protected function manyToMany($accessor, $relationId, $relationForeignKey )
    {
        $modelClass = $this->getModelClass();

        if( ! isset($this->relations[ $relationId ]) ) {
            throw new Exception("Relation $relationId is not defined.");
        }

        // $relation = $this->relations[ $relationId ];
        $this->relations[ $accessor ] = array(
            'type'           => self::many_to_many,
            'relation'       => $relationId,
            'relation_foreign_key'  => $relationForeignKey,
        );
    }


}

