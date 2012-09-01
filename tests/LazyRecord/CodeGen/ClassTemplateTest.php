<?php

class ClassTemplateTest extends PHPUnit_Framework_TestCase
{
    function testUse()
    {
        $use = new LazyRecord\CodeGen\UseClass('\Foo\Bar');
        is( 'Foo\Bar', $use->class );
    }

    function testClassTemplate() 
    {
        $class1 = new LazyRecord\CodeGen\ClassTemplate('Foo\Bar',array(
            'template' => 'Class.php.twig',
            'template_dirs' => array('src/LazyRecord/Schema/Templates'),
        ));
        ok($class1);
    }

}

