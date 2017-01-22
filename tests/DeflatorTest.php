<?php

class DeflatorTest extends PHPUnit_Framework_TestCase
{
    function testInt()
    {
        is( 1, Maghead\Deflator::deflate( '1', 'int' ) );
    }

    function testDatetime() 
    {
        $d = new DateTime;
        $dstr = Maghead\Deflator::deflate( $d , 'DateTime' );
        is( $d->format(DateTime::ATOM) , $dstr);
        is(null, Maghead\Deflator::deflate( '' , 'DateTime' ));
        is(null, Maghead\Deflator::deflate( null , 'DateTime' ));
    }

    function testFloat()
    {
        is( 1.1, Maghead\Deflator::deflate( '1.1', 'float' ) );
    }

    function testStr()
    {
        is( '1', Maghead\Deflator::deflate( 1 , 'str' ) );
        is( '1.1', Maghead\Deflator::deflate( 1.1 , 'str' ) );
    }

    function testBool()
    {
        is( 1 , Maghead\Deflator::deflate( 1.1 , 'bool' ) );
        is( 0 , Maghead\Deflator::deflate( 0 , 'bool' ) );
        is( null , Maghead\Deflator::deflate( null , 'bool' ) );
        is( false , Maghead\Deflator::deflate( '' , 'bool' ));
        is( false , Maghead\Deflator::deflate( '0' , 'bool' ));
        is( true , Maghead\Deflator::deflate( '1' , 'bool' ));
        is( true , Maghead\Deflator::deflate( 'true' , 'bool' ));
        is( false , Maghead\Deflator::deflate( 'false' , 'bool' ));
    }

}

