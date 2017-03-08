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
            ->required()
            ->findable()
            ;

        $this->writeTo('node_master');
        $this->readFrom('node_master');
        $this->globalTable("M_store_id"); // global table on the shards of M_store_id
    }
}
