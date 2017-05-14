<?php
namespace TestApp\Model;

use Maghead\Schema\DeclareSchema;

class WineCategorySchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128);
    }
}
