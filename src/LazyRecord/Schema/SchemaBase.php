<?php
namespace LazyRecord\Schema;

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
