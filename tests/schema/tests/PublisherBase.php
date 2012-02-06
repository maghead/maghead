<?php
namespace tests;



class PublisherBase 
	extends \Lazy\BaseModel
{

	const schema_proxy_class = '\\tests\\PublisherSchemaProxy';
	const collection_class = '\\tests\\PublisherCollection';
	const model_class = '\\tests\\Publisher';

}
