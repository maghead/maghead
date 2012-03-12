<?php
namespace Lazy\Schema;
use Lazy\Schema\SchemaDeclare\Column;
use Lazy\Inflector;
use Lazy\ConfigLoader;
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

    public $label;

    public $table;

    public $mixins = array();

    public $readSourceId;

    public $writeSourceId;

    public $dataSourceId = 'default';

    public function __construct()
    {
        $this->build();
    }

    abstract function schema();

    /**
     * bootstrap script (to create basedata)
     *
     * @param $record current model object.
     */
    public function bootstrap($record) 
    {

    }


    public function build()
    {

        $this->schema();


        /* find primary key */
        foreach( $this->columns as $name => $column ) {
            if( $column->primary )
                $this->primaryKey = $name;
        }

        if( null === $this->primaryKey && $config = ConfigLoader::getInstance() )
        {
            if( $config->loaded && $config->hasAutoId() && ! isset($this->columns['id'] ) ) {
                $this->column('id')
                    ->isa('int')
                    ->integer()
                    ->primary()
                    ->autoIncrement();
                $this->primaryKey = 'id';
            }
        }
    }

    public function export()
    {
        $columnArray = array();
        foreach( $this->columns as $name => $column ) {
            $columnArray[ $name ] = $column->export();
        }

        return array(
            'label'            => $this->getLabel(), // model label
            'table'            => $this->getTable(),
            'columns'          => $columnArray,
            'column_names'     => $this->columnNames,
            'primary_key'      => $this->primaryKey,
            'model_class'      => $this->getModelClass(),
            'collection_class' => $this->getCollectionClass(),
            'relations'      => $this->relations,
            'data_source_id' => $this->dataSourceId,
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
        return $this->table ?: $this->_classnameToTable();
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

    public function getDir()
    {
        $refl = new \ReflectionObject($this);
        return dirname($refl->getFilename());
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
        $name = $this->getModelName();

        if( preg_match( '/(\w+?)(?:Model)?$/', $name ,$reg) ) 
        {
            $table = @$reg[1];
            if( ! $table )
                throw new Exception( "Table name error: $name" );

            /* convert BlahBlah to blah_blah */
            $table =  strtolower( preg_replace( 
                '/(\B[A-Z])/e' , 
                "'_'.strtolower('$1')" , 
                $table ) );

            $inf = Inflector::getInstance();
            return $inf->pluralize( $table );
        } 
        else 
        {
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
        if( isset($this->relations[ $relationId ]) ) {
            return $this->relations[ $relationId ];
        }
    }



    /**
     * define self primary key to foreign key reference
     *
     * comments(
     *    post_id => author.comment_id
     * )
     *
     * $post->publisher
     *
     * @param string $foreignClass foreign schema class.
     * @param string $foreignColumn foreign reference schema column.
     * @param string $selfKey self reference key. (default by id)
     */
    protected function belongsTo($accessor, $foreignClass,$foreignColumn)
    {
        $this->relations[ 'belongs_to:' . $foreignClass ] = array(
            'type' => self::belongs_to,
            'self' => array(
                'schema' => $this->getSchemaProxyClass(),
                'column' => $this->primaryKey,
            ),
            'foreign' => array(
                'schema' => $foreignClass,
                'column' => $foreignColumn,
            )
        );
    }



    /**
     * hasOne relationship
     *
     *   model(
     *      post_id => post
     *   )
     *
     */
    protected function hasOne($accessor,$selfColumn,$foreignClass,$foreignColumn = null)
    {
        // foreignColumn is default to foreignClass.primary key

        // $this->accessors[ $accessor ] = array( );
        $this->relations[ $accessor ] = array(
            'type'           => self::has_one,
            'self'           => array(
                'column'  => $selfColumn,
                'schema' => $this->getSchemaProxyClass(),
            ),
            'foreign' => array(
                'column' => $foreignColumn,
                'schema' => $foreignClass,
            ),
        );
    }




    protected function hasMany($accessor,$foreignClass,$foreignColumn,$selfColumn)
    {
        $this->relations[ $accessor ] = array(
            'type'           => self::has_many,
            'self' => array(
                'column'           => $selfColumn,
                'schema'           => $this->getSchemaProxyClass(),
            ),
            'foreign'  => array( 
                'column' => $foreignColumn,
                'schema' => $foreignClass,
            )
        );
    }

    protected function manyToMany($accessor, $relationId, $relationForeignKey )
    {
        $modelClass = $this->getModelClass();
        if( ! isset($this->relations[ $relationId ]) ) {
            throw new Exception("Relation $relationId is not defined.");
        }

        $this->relations[ $accessor ] = array(
            'type'           => self::many_to_many,
            'relation'       => $relationId,
            'relation_foreign_key'  => $relationForeignKey,
        );
    }

    public function getReferenceSchemas($recursive = true)
    {
        $schemas = array();
        foreach( $this->relations as $rel ) {
            if( ! isset($rel['foreign']['schema']) )
                continue;

            $class = ltrim($rel['foreign']['schema'],'\\');
            $fs = new $class;
            if( isset($schemas[$class]) )
                continue;

            $schemas[ $class ] = 1;
            if( $recursive )
                $schemas = array_merge($schemas, $fs->getReferenceSchemas(false));
        }
        return $schemas;
    }




    public function getLabel()
    {
        return $this->label ?: $this->_modelClassToLabel();
    }

    protected function _modelClassToLabel() 
    {
        /* Get the latest token. */
        if( preg_match( '/(\w+)(?:Model)?$/', $this->getModelClass() , $reg) ) 
        {
            $label = @$reg[1];
            if( ! $label )
                throw new Exception( "Table name error" );

            /* convert blah_blah to BlahBlah */
            return ucfirst(preg_replace( '/[_]/' , ' ' , $label ));
        }
    }


}

