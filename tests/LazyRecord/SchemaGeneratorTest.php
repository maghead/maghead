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
		$classMap = $generator->generate();

		foreach( $classMap as $class => $file ) {
			var_dump( $file ); 
			path_ok( $file , $class );
#  			unlink( $file );
		}

		$author = new \tests\Author;
		$book = new \tests\Book;

        ok( $author );
        ok( $book );

        $schemaProxy = new \tests\AuthorSchemaProxy;
        ok( $schemaProxy->table );
        ok( $schemaProxy->columns );
        ok( $schemaProxy->modelClass );

        ok( $author::model_class );
        ok( $author::schema_proxy_class );
	}

}
