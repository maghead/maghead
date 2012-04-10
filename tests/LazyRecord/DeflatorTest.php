<?php

class DeflatorTest extends PHPUnit_Framework_TestCase
{
    function testInt()
    {
        is( 1, LazyRecord\Deflator::deflate( '1', 'int' ) );
    }


    function testFloat()
    {
        is( 1.1, LazyRecord\Deflator::deflate( '1.1', 'float' ) );
    }

    function testStr()
    {
        is( '1', LazyRecord\Deflator::deflate( 1 , 'str' ) );
        is( '1.1', LazyRecord\Deflator::deflate( 1.1 , 'str' ) );
    }

    function testBool()
    {
        is( 1 , LazyRecord\Deflator::deflate( 1.1 , 'bool' ) );
        is( 0 , LazyRecord\Deflator::deflate( 0 , 'bool' ) );
        is( null , LazyRecord\Deflator::deflate( null , 'bool' ) );
    }

}

