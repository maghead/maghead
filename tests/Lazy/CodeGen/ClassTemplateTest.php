<?php

class ClassTemplateTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $class = new Lazy\CodeGen\ClassTemplate('Foo');
        is( 'Foo', $class->class->name );
    }

    function testNsName()
    {
        $class = new Lazy\CodeGen\ClassTemplate('Bar\Foo');
        is( 'Foo', $class->class->name );
        is( 'Bar', $class->class->namespace );
    }

	function testUse()
	{
		$use = new Lazy\CodeGen\UseClass('\Foo\Bar');
		is( 'Foo\Bar', $use->class );
	}

}

