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

        $code = $class1->render();

        $tmpname = tempnam('/tmp','FOO');
        file_put_contents($tmpname, $code);
        require $tmpname;

        unlink($tmpname);
    }

}

