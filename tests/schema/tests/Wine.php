<?php
namespace tests;
use LazyRecord\BaseModel;

class Wine extends BaseModel
{
    public function schema($schema)
    {
        $schema->column('name')
            ->varchar(128);

        $schema->column('years')
            ->integer();

        $schema->column('category_id')
            ->refer('tests\\WineCategory');
    }
#boundary start 7c7b528eb73d02172dcec82d792e1699
	const schema_proxy_class = 'tests\\WineSchemaProxy';
	const collection_class = 'tests\\WineCollection';
	const model_class = 'tests\\Wine';
	const table = 'wines';
#boundary end 7c7b528eb73d02172dcec82d792e1699
}
