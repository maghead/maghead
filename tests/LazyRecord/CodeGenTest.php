<?php

class CodeGenTest extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$codegen = new LazyRecord\CodeGen( 'src/LazyRecord/Schema/Templates' );
		ok( $codegen );
	}
}

