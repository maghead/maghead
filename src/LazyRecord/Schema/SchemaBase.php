<?php
namespace LazyRecord\Schema;
use RuntimeException;

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

            $fs = new $class;
            if( isset($schemas[$class]) )
                continue;

            $schemas[ $class ] = 1;
            if( $recursive )
                $schemas = array_merge($schemas, $fs->getReferenceSchemas(false));
        }
        return $schemas;
    }

}
