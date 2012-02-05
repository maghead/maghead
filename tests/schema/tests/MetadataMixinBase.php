<?php
namespace tests;



class MetadataMixinBase 
	extends \LazyRecord\BaseModel
{

	const schema_proxy_class = '\\tests\\MetadataMixinSchemaProxy';
	const collection_class = '\\tests\\MetadataMixinCollection';
	const model_class = '\\tests\\MetadataMixin';

}
