<?php
namespace Lazy\Schema;

abstract class SchemaBase
{
    const has_one = 1;

    const has_many = 2;

    const many_to_many = 3;

    const belongs_to = 4;

    public function getRelation($relationId)
    {
        if( isset($this->relations[ $relationId ]) ) {
            return $this->relations[ $relationId ];
        }
    }

}
