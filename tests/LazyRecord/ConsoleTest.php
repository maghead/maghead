<?php

class ConsoleTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $this->expectOutputRegex('/LazyRecord/');
        $console = new LazyRecord\Console;
        ok($console);
        $console->run(array());
    }
}

