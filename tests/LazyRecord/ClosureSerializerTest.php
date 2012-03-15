<?php

class ClosureSerializerTest extends PHPUnit_Framework_TestCase
{
    function test()
    {

        $x = 3;
        $a = function() use($x) {
            // content
            return 123;
        };
        $content = LazyRecord\ClosureSerializer::serialize( $a );
        ok( $content );

        eval( '$b = ' . $content . ';' );
        ok( $b );
    }
}

