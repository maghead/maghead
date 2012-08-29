<?php
namespace tests;
use LazyRecord\BaseModel;

class Wine extends BaseModel
{
    function schema($schema)
    {
        $schema->column('name')
            ->varchar(128);

        $schema->column('years')
            ->integer();
    }
}
