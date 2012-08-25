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


    public function getReadSourceId()
    {
        return $this->readSourceId;
    }

    public function getWriteSourceId()
    {
        return $this->writeSourceId;
    }


    public function getRelation($relationId)
    {
        if( isset($this->relations[ $relationId ]) ) {
            return $this->relations[ $relationId ];
        }
    }

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
            if( ! isset($rel['foreign']['schema']) )
                continue;

            $class = ltrim($rel['foreign']['schema'],'\\');
            if( ! class_exists($class,true) ) {
                throw new RuntimeException("Foreign schema class $class not found." );
            }

            if( ! is_subclass_of( $class, 'LazyRecord\Schema\SchemaDeclare' ) ) {
                throw new InvalidArgumentException("Foreign schema class $class is not a SchemaDeclare class");
            }
            if( isset($schemas[$class]) ) {
                continue;
            }

            $schemas[ $class ] = 1;
            $fs = new $class;
            if( $recursive ) {
                $schemas = array_merge($schemas, $fs->getReferenceSchemas(false));
            }
        }
        return $schemas;
    }

}
