<?php
use LazyRecord\SchemaSqlBuilder;

class ModelTest extends PHPUnit_Framework_TestCase
{
	function getLogger()
	{
		return new TestLogger;
	}

    function pdoQueryOk($dbh,$sql)
    {
		$ret = $dbh->query( $sql );

		$error = $dbh->errorInfo();
		if($error[1] != null ) {
            throw new Exception( 
                var_export( $error, true ) 
                . ' SQL: ' . $sql 
            );
		}
        // ok( $error[1] != null );
        return $ret;
    }

	function testSqlite()
	{
		if( file_exists('tests.db') ) {
			unlink('tests.db');
		}



        // build schema 
		$dbh = new PDO('sqlite:tests.db'); // success
		$builder = new SchemaSqlBuilder('sqlite');
		ok( $builder );

		$s = new \tests\AuthorSchema;
		$authorbook = new \tests\AuthorBookSchema;
		$bookschema = new \tests\BookSchema;
		ok( $s );

		$sql = $builder->build($s);
		ok( $sql );
        // var_dump( $sql ); 
        $this->pdoQueryOk( $dbh , $sql );


		ok( $authorbook );
		$sql = $builder->build($authorbook);
		ok( $sql );
        // var_dump( $sql ); 

        $this->pdoQueryOk( $dbh , $sql );


		ok( $bookschema );
		$sql = $builder->build($bookschema);
		ok( $sql );
        // var_dump( $sql ); 

        $this->pdoQueryOk( $dbh , $sql );


		$generator = new \LazyRecord\SchemaGenerator;
		$generator->addPath( 'tests/schema/' );
		$generator->setLogger( $this->getLogger() );
		$generator->setTargetPath( 'tests/build/' );
		$classMap = $generator->generate();
        ok( $classMap );

        return;

        /* Basic CRUD Test */
        $author = new Author;
        $ret = $author->create(array( ));

        $author->update(array( ));

        $author->delete();

        /**
         * Static CRUD Test 
         */
        Author::create(array( 
            'name' => 'Mary'
        ));

        Author::update(array( 'name' => 'Rename' ))
            ->where()->equal('')
            ->back()->execute();

        Author::delete()
            ->where()->equal('')
            ->back()->execute();


	}
}

