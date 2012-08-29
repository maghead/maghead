<?php

class ClassInjectionTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        // create test class file
        file_put_contents('tests/tmp_class',<<<'CODE'
<?php
class InjectFoo {
    public $value = 2;
    public function __toString() {
        return $this->getValue();
    }
}
CODE
);
        require 'tests/tmp_class';
        $foo = new InjectFoo;
        ok($foo);

        $inject = new LazyRecord\CodeGen\ClassInjection($foo);
        ok($inject);

        $inject->read();


        // so that we have getValue method now.
        $inject->appendContent('
            function getValue() {
                return $this->value;
            }
        ');

        $inject->write();

        is( file_get_contents('tests/data/injected'), $inject->__toString() );

        $inject->replaceContent('');
        $inject->write();

        // TODO test the content
        unlink('tests/tmp_class');
    }
}

