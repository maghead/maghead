<?php

class ClassTemplateTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $class = new LazyRecord\CodeGen\ClassTemplate('Foo');
        is( 'Foo', $class->class->name );
    }

    function testNsName()
    {
        $class = new LazyRecord\CodeGen\ClassTemplate('Bar\Foo');
        is( 'Foo', $class->class->name );
        is( 'Bar', $class->class->namespace );
    }

	function testUse()
	{
		$use = new LazyRecord\CodeGen\UseClass('\Foo\Bar');
		is( 'Foo\Bar', $use->class );
	}

}

