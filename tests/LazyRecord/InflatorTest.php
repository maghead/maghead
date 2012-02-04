<?php

class InflatorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        is( 1, LazyRecord\Inflator::inflate( '1', 'int' ) );
        is( 1.1, LazyRecord\Inflator::inflate( '1.1', 'float' ) );
        is( '1', LazyRecord\Inflator::inflate( 1 , 'str' ) );
        is( '1.1', LazyRecord\Inflator::inflate( 1.1 , 'str' ) );
        is( true , LazyRecord\Inflator::inflate( 1.1 , 'bool' ) );
        is( false , LazyRecord\Inflator::inflate( 0 , 'bool' ) );
    }
}

