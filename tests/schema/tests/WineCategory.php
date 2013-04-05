<?php
namespace tests;
use LazyRecord\BaseModel;

class WineCategory extends BaseModel
{
    public function schema($schema)
    {
        $schema->column('name')
            ->varchar(128);
    }


    public function dataLabel()
    {
        return $this->name;
    }


#boundary start e2e1f893c22733c4a9299bab2b5f85f8
	const schema_proxy_class = 'tests\\WineCategorySchemaProxy';
	const collection_class = 'tests\\WineCategoryCollection';
	const model_class = 'tests\\WineCategory';
	const table = 'wine_categories';
#boundary end e2e1f893c22733c4a9299bab2b5f85f8
}
