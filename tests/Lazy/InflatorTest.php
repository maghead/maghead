<?php

class InflatorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        is( 1, Lazy\Inflator::inflate( '1', 'int' ) );
        is( 1.1, Lazy\Inflator::inflate( '1.1', 'float' ) );
        is( '1', Lazy\Inflator::inflate( 1 , 'str' ) );
        is( '1.1', Lazy\Inflator::inflate( 1.1 , 'str' ) );
        is( 'TRUE' , Lazy\Inflator::inflate( 1.1 , 'bool' ) );
        is( 'FALSE' , Lazy\Inflator::inflate( 0 , 'bool' ) );
    }
}

