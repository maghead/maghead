<?php
namespace LazyRecord\Schema;
use Exception;
use ReflectionObject;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;

class SchemaDeclare extends SchemaBase
    implements SchemaDataInterface
{
    public function __construct()
    {
        $this->build();
    }

    public function schema() 
    {

    }

    public function writeTo( $id ) 
    {
        $this->writeSourceId = $id;
        return $this;
    }

    public function readFrom( $id ) 
    {
        $this->readSourceId = $id;
        return $this;
    }

    /**
     * bootstrap script (to create basedata)
     *
     * @param $record current model object.
     */
    public function bootstrap($record) 
    {

    }


    public function getColumns()
    {
        return $this->columns;
    }

    public function getColumn($name)
    {
        if( isset($this->columns[ $name ]) ) {
            return $this->columns[ $name ];
        }
    }

    public function hasColumn($name)
    {
        return isset($this->columns[ $name ]);
    }


    /**
     * Build schema
     */
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
            // XXX: can we prepend ?
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
            'label'             => $this->getLabel(),
            'table'             => $this->getTable(),
            'columns'           => $columnArray,
            'column_names'      => $this->columnNames,
            'primary_key'       => $this->primaryKey,
            'model_class'       => $this->getModelClass(),
            'collection_class'  => $this->getCollectionClass(),
            'relations'         => $this->relations,
            'read_data_source'  => $this->readSourceId,
            'write_data_source' => $this->writeSourceId,
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


    /**
     * Mixin
     *
     * Availabel mixins
     *
     *     $this->mixin('Metadata');
     *     $this->mixin('I18n');
     *
     * @param string $class mixin class name
     */
    public function mixin($class)
    {
        if( ! class_exists($class,true) ) {
            $class = 'LazyRecord\Schema\Mixin\\' . $class;
            if( ! class_exists($class,true) ) {
                throw new Exception("Mixin class $class not found.");
            }
        }

        $mixin = new $class;
        $this->mixins[] = $class;

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
        // If self class name is endded with 'Schema', remove it and return.
        $class = get_class($this);
        if( ( $p = strrpos($class, 'Schema' ) ) !== false ) {
            return substr($class , 0 , $p);
        }
        // throw new Exception('Can not get model class from ' . $class );
        return $class;
    }


    /**
     * Get full class name
     */
    public function getClass()
    {
        return get_class($this);
    }


    /**
     * Get directory from current schema object.
     *
     * @return string path
     */
    public function getDir()
    {
        $refl = new \ReflectionObject($this);
        return dirname($refl->getFilename());
    }


    /**
     * Convert current model name to a class name.
     *
     * @return string table name
     */
    protected function _classnameToTable() 
    {
        return ClassUtils::convert_class_to_table( $this->getModelName() );
    }

    /**
     * Define new column object
     *
     * @param string $name column name
     * @return SchemaDeclare\Column
     */
    public function column($name)
    {
        if( isset($this->columns[$name]) ) {
            throw new Exception("column $name of ". get_class($this) . " is already defined.");
        }
        $this->columnNames[] = $name;
        return $this->columns[ $name ] = new SchemaDeclare\Column( $name );
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
     * @param string $selfColumn self column name
     */
    protected function belongsTo($accessor, $foreignClass, $foreignColumn = null,  $selfColumn = 'id')
    {
        if( null === $foreignColumn ) {
            $s = new $foreignClass;
            $foreignColumn = $s->primaryKey;
        }

        $this->relations[ $accessor ] = array(
            'type' => self::belongs_to,
            'self' => array(
                'schema' => get_class($this),
                'column' => $selfColumn,
            ),
            'foreign' => array(
                'schema' => $foreignClass,
                'column' => $foreignColumn,
            )
        );
    }



    /**
     * has-one relationship
     *
     *   model(
     *      post_id => post
     *   )
     */
    protected function one($accessor,$selfColumn,$foreignClass,$foreignColumn = null)
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



    /**
     * Add has-many relation
     */
    protected function many($accessor,$foreignClass,$foreignColumn,$selfColumn)
    {
        $this->relations[ $accessor ] = array(
            'type' => self::has_many,
            'self' => array(
                'column'           => $selfColumn,
                'schema'           => get_class($this),
            ),
            'foreign'  => array( 
                'column' => $foreignColumn,
                'schema' => $foreignClass,
            )
        );
    }


    /**
     *
     * @param string $accessor   accessor name
     * @param string $relationId a hasMany relationship 
     */
    protected function manyToMany($accessor, $relationId, $foreignRelationId )
    {
        if( $r = $this->getRelation($relationId) ) {
            $this->relations[ $accessor ] = array(
                'type'           => self::many_to_many,
                'relation'        => array( 
                    'id'             => $relationId,
                    'id2'            => $foreignRelationId,
                ),
            );
            return;
        }
        throw new Exception("Relation $relationId is not defined.");
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

