<?php
namespace StoreApp\Model;

use Maghead\Schema\DeclareSchema;

class OrderSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('store_id')
            ->integer()
            ->unsigned()
            ->required(true)
            ;

        $this->column('amount')
            ->integer()
            ->required();
    }
}
