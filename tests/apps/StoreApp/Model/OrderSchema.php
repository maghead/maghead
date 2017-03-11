<?php
namespace StoreApp\Model;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\Column\UUIDPrimaryKeyColumn;
use Ramsey\Uuid\Uuid;

class OrderSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('uuid', 'Maghead\\Schema\\Column\\UUIDPrimaryKeyColumn')
            ->default(function($record, $args) {
                return \Ramsey\Uuid\Uuid::uuid4()->getBytes();
            })
            ->deflate(function($val) {
                if ($val instanceof \Ramsey\Uuid\Uuid) {
                    return $val->getBytes();
                }
                return $val;
            })
            ->inflate(function($val) {
                return \Ramsey\Uuid\Uuid::fromBytes($val);
            });
            ;

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
