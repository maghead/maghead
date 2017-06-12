<?php
namespace StoreApp\Model;

use Maghead\Schema\DeclareSchema;

/**
 * @platform mysql
 */
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

        // This will be default to the master node ID
        // $this->writeTo('master');
        // $this->readFrom('master');
        $this->globalTable("M_store_id"); // global table on the shards of M_store_id
    }
}
