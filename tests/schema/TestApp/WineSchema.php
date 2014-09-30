<?php
namespace TestApp;
use LazyRecord\BaseModel;
use LazyRecord\Schema;

class WineSchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128);

        $this->column('years')
            ->integer();

        $this->column('category_id')
            ->refer('TestApp\\WineCategorySchema');
    }
}
