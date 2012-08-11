<?php

class ArrayUtilsTest extends PHPUnit_Framework_TestCase
{

    function arrayProvider()
    {
        return array(
            array(array(  
                'a' => 'b',
                '0' => '1',
            )),
            array(array(
                'foo' => 1,
                'bar' => 2,
            )),
        );
    }


    /**
     * @dataProvider arrayProvider
     */
    function test($array)
    {
        ok( LazyRecord\ArrayUtils::is_assoc_array( $array ) );
        ok( ! LazyRecord\ArrayUtils::is_indexed_array( $array ) );
    }
}

