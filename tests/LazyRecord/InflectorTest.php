<?php

class InflectorTest extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$inflector = \LazyRecord\Inflector::getInstance();
		is( 'posts' , $inflector->pluralize('post') );
		is( 'blogs' , $inflector->pluralize('blog') );
		is( 'categories' , $inflector->pluralize('category') );
	}
}

