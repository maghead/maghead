<?php

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{

	function getLogger()
	{
		return new TestLogger;
	}


	function test()
	{
		$generator = new LazyRecord\SchemaGenerator;
		$generator->addPath( 'tests/schema/' );
		$generator->setLogger( $this->getLogger() );
		$generator->setTargetPath( 'tests/build/' );
		$generator->generate();

		$files = array();
		$files[] = 'tests/build/BookSchemaProxy.php';
		$files[] = 'tests/build/tests/AuthorSchemaProxy.php';
		$files[] = 'tests/build/tests/AuthorBookSchemaProxy.php';
		$files[] = 'tests/build/tests/BookSchemaProxy.php';

		foreach($files as $file ) {
			path_ok( $file );
			unlink( $file );
		}

	}

}
