<?php

class CodeGenTest extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$codegen = new Lazy\CodeGen( 'src/Lazy/Schema/Templates' );
		ok( $codegen );
	}
}

