<?php
namespace tests;



class AuthorBookCollectionBase 
	extends \LazyRecord\BaseCollection
{

	const schema_proxy_class = '\\tests\\AuthorBookSchemaProxy';
	const model_class = '\\tests\\AuthorBook';

}
