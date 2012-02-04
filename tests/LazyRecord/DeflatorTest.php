<?php

class DeflatorTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $d = new \DateTime( '2010-01-31' );
        ok( $d );

        $d = new \DateTime('2010-01-31T00:00:00+08:00');
        ok( $d );

        $d = new \DateTime('2012-01-19 03:10:41');
        ok( $d );
        is( '2012-01-19T03:10:41+08:00', $d->format( DateTime::ATOM  ) ); 


        is( false , LazyRecord\Deflator::deflate( 'false', 'bool' ) );
        is( false , LazyRecord\Deflator::deflate( 'FALSE', 'bool' ) );
        is( false , LazyRecord\Deflator::deflate( '0', 'bool' ) );

        is( true , LazyRecord\Deflator::deflate( 'true', 'bool' ) );
        is( true , LazyRecord\Deflator::deflate( 'TRUE', 'bool' ) );
        is( true , LazyRecord\Deflator::deflate( '1', 'bool' ) );

        is( 1.1 , LazyRecord\Deflator::deflate( '1.1', 'float' ) );
    }
}

