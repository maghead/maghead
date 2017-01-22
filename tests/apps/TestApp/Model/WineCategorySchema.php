<?php
namespace TestApp\Model;
use Maghead\Schema;

class WineCategorySchema extends Schema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(128);
    }
}
