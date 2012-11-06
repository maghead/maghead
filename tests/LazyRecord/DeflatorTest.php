<?php

class DeflatorTest extends PHPUnit_Framework_TestCase
{
    function testInt()
    {
        is( 1, LazyRecord\Deflator::deflate( '1', 'int' ) );
    }

    function testDatetime() 
    {
        $d = new DateTime;
        $dstr = LazyRecord\Deflator::deflate( $d , 'DateTime' );
        is( $d->format(DateTime::ATOM) , $dstr);
        is(null, LazyRecord\Deflator::deflate( '' , 'DateTime' ));
        is(null, LazyRecord\Deflator::deflate( null , 'DateTime' ));
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
        is( false , LazyRecord\Deflator::deflate( '' , 'bool' ));
        is( false , LazyRecord\Deflator::deflate( '0' , 'bool' ));
        is( true , LazyRecord\Deflator::deflate( '1' , 'bool' ));
        is( true , LazyRecord\Deflator::deflate( 'true' , 'bool' ));
        is( false , LazyRecord\Deflator::deflate( 'false' , 'bool' ));
    }

}

