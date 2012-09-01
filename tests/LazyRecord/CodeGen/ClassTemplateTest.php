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
        $class1 = new LazyRecord\CodeGen\ClassTemplate('Foo\Bar22',array(
            'template' => 'Class.php.twig',
            'template_dirs' => array('src/LazyRecord/Schema/Templates'),
        ));
        ok($class1);

        $class1->addMethod('public','getTwo',array(),'return 2;');
        $class1->addMethod('public','getFoo',array('i'),'return $i;');
        $code = $class1->render();
        $tmpname = tempnam('/tmp','FOO');
        file_put_contents($tmpname, $code);
        require $tmpname;

        ok(class_exists('Foo\Bar22'));

        $bar22 = new Foo\Bar22;
        ok($bar22);

        is(2,$bar22->getTwo());

        is(3,$bar22->getFoo(3));

        unlink($tmpname);
    }

}

