<?php


/* test helpers */
function result_ok( $result )
{
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $tester = $stacks[1]['object'];
    $tester->assertTrue( $result ? true : false );
    $tester->assertTrue( $result->success, $result->message );
    return $result;
}

function result_fail( $result ) {
    $stacks = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT ); 
    $tester = $stacks[1]['object'];
    $tester->assertTrue( $result ? true : false );
    $tester->assertFalse( $result->success, $result->message );
    return $result;
}

