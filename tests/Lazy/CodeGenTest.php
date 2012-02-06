<?php

class CodeGenTest extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$codegen = new Lazy\CodeGen( 'src/Lazy/Templates' );
		ok( $codegen );
	}
}

