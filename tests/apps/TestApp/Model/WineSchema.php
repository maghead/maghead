<?php
namespace TestApp\Model;

use Maghead\Runtime\Model;
use Maghead\Schema\DeclareSchema;

class WineSchema extends DeclareSchema
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
