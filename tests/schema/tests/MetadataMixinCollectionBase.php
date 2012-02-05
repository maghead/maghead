<?php
namespace tests;



class MetadataMixinCollectionBase 
	extends \LazyRecord\BaseCollection
{

	const schema_proxy_class = '\\tests\\MetadataMixinSchemaProxy';
	const model_class = '\\tests\\MetadataMixin';

}
