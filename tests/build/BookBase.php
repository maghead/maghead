<?php



class BookBase 
	extends \LazyRecord\BaseModel
{

	const schema_proxy_class = '\\BookSchemaProxy';
	const collection_class = '\\BookCollection';
	const model_class = '\\Book';

}
