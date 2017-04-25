<?php
namespace StoreApp\Model;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Column\UUIDPrimaryKeyColumn;
use Ramsey\Uuid\Uuid;

class OrderSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('uuid', 'uuid-pk');

        $this->column('store_id')
            ->integer()
            ->unsigned()
            ->required(true)
            ;

        $this->column('amount')
            ->integer()
            ->required();

        $this->column('paid_amount')
            ->integer()
            ->default(0);

        $this->column('paid')
            ->boolean()
            ->default(false);

        $this->shardBy("M_store_id");
    }
}
