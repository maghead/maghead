<?php
namespace StoreApp\Model;
use Maghead\Schema\DeclareSchema;

class StoreSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('name')
            ->varchar(32);

        $this->column('code')
            ->varchar(12)
            ->required();
    }
}



