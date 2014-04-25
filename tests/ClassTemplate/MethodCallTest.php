<?php

class MethodCallTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $call = new ClassTemplate\MethodCall;
        $call->method('doSomething');
        $call->addArgument('\'123\'');
        $call->addArgument('\'foo\'');
        $call->addArgument(array( 'name' => 'hack' ));
        $str = $call->render();
        ok($str);
        is("\$this->doSomething('123','foo',array (\n  'name' => 'hack',\n));",$str);
    }
}

