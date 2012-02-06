<?php

class InflectorTest extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$inflector = \Lazy\Inflector::getInstance();
		is( 'posts' , $inflector->pluralize('post') );
		is( 'blogs' , $inflector->pluralize('blog') );
		is( 'categories' , $inflector->pluralize('category') );
	}
}

