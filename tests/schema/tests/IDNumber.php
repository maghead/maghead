<?php
namespace tests;
use LazyRecord\BaseModel;

class IDNumber extends BaseModel
{
    public function schema($schema) 
    {
        $schema->column('id_number')
            ->varchar(10)
            ->validator('TW\\IDNumberValidator');
    }
#boundary start 639323d309ee1eb78e47abd816e15519
	const schema_proxy_class = 'tests\\IDNumberSchemaProxy';
	const collection_class = 'tests\\IDNumberCollection';
	const model_class = 'tests\\IDNumber';
	const table = 'i_d_numbers';
#boundary end 639323d309ee1eb78e47abd816e15519
}



