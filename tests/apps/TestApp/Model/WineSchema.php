<?php
namespace TestApp\Model;
use Maghead\BaseModel;
use Maghead\Schema;

class WineSchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128);

        $this->column('years')
            ->integer();

        $this->column('category_id')
            ->integer()
            ->refer('TestApp\\Model\\WineCategorySchema');
    }
}
