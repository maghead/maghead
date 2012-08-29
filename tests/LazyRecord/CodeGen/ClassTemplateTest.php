<?php

class ClassTemplateTest extends PHPUnit_Framework_TestCase
{
    function testUse()
    {
        $use = new LazyRecord\CodeGen\UseClass('\Foo\Bar');
        is( 'Foo\Bar', $use->class );
    }

}

