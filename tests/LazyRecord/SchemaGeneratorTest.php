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
		ok( $author , 'author model' );

		is( '\tests\Author' , \tests\Author::model_class );
		is( '\tests\AuthorSchemaProxy' , \tests\Author::schema_proxy_class );
		is( '\tests\AuthorCollection' , \tests\Author::collection_class );

		$book = new Book;
		ok( $book );

		$bench = new SimpleBench;
		$task = $bench->start('create');
		for( $i = 0 ; $i < 10000 ; $i++ ) {
			$b = new Book;
		}
		$task->end();
		if( $task->rate < 50 ) {
			throw new Exception("Model object contruction too slow! Rate: {$task->rate}");
		}

		/* tear down */
		foreach( $classMap as $class => $file ) {
			unlink( $file );
		}
	}

}
