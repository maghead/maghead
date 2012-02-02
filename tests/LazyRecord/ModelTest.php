<?php
require_once 'tests/schema/AuthorBooks.php';
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


		$generator = new \LazyRecord\SchemaGenerator;
		$generator->addPath( 'tests/schema/' );
		$generator->setLogger( $this->getLogger() );
		$generator->setTargetPath( 'tests/build/' );
		$classMap = $generator->generate();
        ok( $classMap );

        /*******************
         * build schema 
         * ****************/
		$authorschema = new \tests\AuthorSchema;
		$authorbook = new \tests\AuthorBookSchema;
		$bookschema = new \tests\BookSchema;
		ok( $authorschema );

		$sql = $builder->build($authorschema);
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





        /****************************
         * Basic CRUD Test 
         * **************************/
        $author = new \tests\Author;
        ok( $author->schema );

        $ret = $author->create(array());
        ok( $ret );
        ok( ! $ret->success );
        ok( $ret->message );
        is( 'Empty arguments' , $ret->message );

        $query = $author->createQuery();
        ok( $query );

        $ret = $author->create(array( 'name' => 'Foo' ));
        return;
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

