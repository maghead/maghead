<?php
namespace tests;



class BookBase 
	extends \Lazy\BaseModel
{

	const schema_proxy_class = '\\tests\\BookSchemaProxy';
	const collection_class = '\\tests\\BookCollection';
	const model_class = '\\tests\\Book';

}
