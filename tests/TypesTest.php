<?php
use Maghead\Types;
class TypesTest extends PHPUnit_Framework_TestCase
{
    public function testTypes()
    {
        ok( Types::int );
        ok( Types::str );
    }
}

