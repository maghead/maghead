<?php

class CodeGenTest extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$codegen = new LazyRecord\CodeGen( 'src/LazyRecord/Templates' );
		ok( $codegen );
	}
}

