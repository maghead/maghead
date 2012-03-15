<?php
use LazyRecord\Types;
class TypesTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        ok( Types::int );
        ok( Types::str );
        
    }
}

