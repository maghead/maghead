<?php

class ExporterTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $a = array(
            'a' => '123123',
            'b' => 1.24,
            'c' => array(
                'callback' => function() { 
                    return 'foo';
                },
            ),
        );

        $str = LazyRecord\Schema\SchemaDeclare\Exporter::export($a);
        eval('$b = ' . $str . ';');
        is( '123123', $b['a'] );
        is( 1.24, $b['b'] );
        ok( $cb = $b['c']['callback'] );
        ok( is_callable($cb) );
        is( 'foo', $cb() );
    }
}

