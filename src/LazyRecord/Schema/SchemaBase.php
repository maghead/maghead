<?php
namespace LazyRecord\Schema;
use RuntimeException;
use InvalidArgumentException;

abstract class SchemaBase
{
    const has_one = 1;

    const has_many = 2;

    const many_to_many = 3;

    const belongs_to = 4;


    public $table;

    public $label;

    public $columns = array();

    public $columnNames = array();

    public $relations = array();

    public $primaryKey;

    public $readSourceId = 'default';

    public $writeSourceId = 'default';

    public $mixins = array();

    public $seeds = array();

    protected $_namespace;

    protected $_modelName;

    public function getModelName()
    {
        if ( $this->_modelName ) {
            return $this->_modelName;
        }

        $class = $this->getModelClass();
        $p = strrpos($class,'\\');
        if ( $p === false ) {
            return $class;
        }
        return $this->_modelName = substr($class,$p + 1);
    }

    public function getSeeds()
    {
        return $this->seeds;
    }

    // Class name related methods
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

    public function newModel()
    {
        return new $this->getModelClass();
    }

    public function newCollection() 
    {
        return new $this->getCollectionClass();
    }


    /**
     * Get class namespace
     */
    public function getNamespace()
    {
        if ( $this->_namespace ) {
            return $this->_namespace;
        }

        $class = $this->getModelClass();
        $p = strrpos($class, '\\'); 
        if ( $p === false ) {
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
        if( isset($this->relations[ $relationId ]) ) {
            return $this->relations[ $relationId ];
        }
    }


    /**
     * Get relationship data
     */
    public function getRelations() 
    {
        return $this->relations;
    }


    /**
     * For schema class, get its reference schema classes recursively.
     *
     * @param boolean $recursive
     */
    public function getReferenceSchemas($recursive = true)
    {
        $schemas = array();
        foreach( $this->relations as $rel ) {
            if( ! isset($rel['foreign_schema']) )
                continue;

            $class = ltrim($rel['foreign_schema'],'\\');

            if( isset($schemas[$class]) )
                continue;

            if( ! class_exists($class,true) ) {
                throw new RuntimeException("Foreign schema class $class not found." );
            }

            if( is_a($class,'LazyRecord\\BaseModel',true) ) {
                // bless model class to schema object.
                $schemas[ $class ] = 1;
                $model = new $class;
                $schema = new \LazyRecord\Schema\DynamicSchemaDeclare($model);
                if( $recursive ) {
                    $schemas = array_merge($schemas, $schema->getReferenceSchemas(false));
                }
            }
            elseif( is_subclass_of( $class, 'LazyRecord\\Schema\\SchemaDeclare',true ) ) {
                $schemas[ $class ] = 1;
                $fs = new $class;
                if( $recursive ) {
                    $schemas = array_merge($schemas, $fs->getReferenceSchemas(false));
                }
            }
            else {
                throw new InvalidArgumentException("Foreign schema class $class is not a SchemaDeclare class");
            }

        }
        return $schemas;
    }

}
