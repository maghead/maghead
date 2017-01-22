<?php

class ArrayUtilsTest extends PHPUnit_Framework_TestCase
{
    public function testAssocArrayCheck()
    {

        $a = array(
            'a' => 'b',
            '0' => '1',
        );
        ok( Maghead\ArrayUtils::is_assoc_array($a) );

        $a = array( 
            'foo' => 'b',
            'bar' => '1',
        );
        ok( Maghead\ArrayUtils::is_assoc_array($a) );
    }

    public function testIndexedArrayCheck()
    {
        $a = array( 
            0 => 'foo',
            1 => 'bar',
        );
        ok( Maghead\ArrayUtils::is_indexed_array($a));

        $a = array( 
            'a' => 'foo',
            1 => 'bar',
        );
        not_ok( Maghead\ArrayUtils::is_indexed_array( $a ));


        $a = array(
            'a' => 'foo',
            1 => 'bar',
        );
        not_ok( Maghead\ArrayUtils::is_indexed_array($a));
    }
}

