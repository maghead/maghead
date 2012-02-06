<?php
namespace tests;



class AuthorCollectionBase 
	extends \Lazy\BaseCollection
{

	const schema_proxy_class = '\\tests\\AuthorSchemaProxy';
	const model_class = '\\tests\\Author';

}
