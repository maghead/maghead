<?php
use LazyRecord\SchemaSqlBuilder;

class SchemaSqlBuilderTest extends PHPUnit_Framework_TestCase
{

    function pdoQueryOk($dbh,$sql)
    {
		$ret = $dbh->query( $sql );
		ok( $ret );
		$error = $dbh->errorInfo();
		if($error[1] != null ) {
			throw new Exception( var_export( $error, true ) );
		}
        // ok( $error[1] != null );
        return $ret;
    }

	function testSqlite()
	{
		if( file_exists('tests.db') ) {
			unlink('tests.db');
		}

		$dbh = new PDO('sqlite:tests.db'); // success
		$builder = new SchemaSqlBuilder('sqlite');
		ok( $builder );

		$s = new \tests\AuthorSchema;
		ok( $s );

		$sql = $builder->build($s);
		ok( $sql );
        var_dump( $sql ); 
        $this->pdoQueryOk( $dbh , $sql );


		$authorbook = new \tests\AuthorBookSchema;
		ok( $authorbook );
		$sql = $builder->build($authorbook);
		ok( $sql );
        var_dump( $sql ); 

        $this->pdoQueryOk( $dbh , $sql );


		$bookschema = new \tests\BookSchema;
		ok( $bookschema );
		$sql = $builder->build($bookschema);
		ok( $sql );
        var_dump( $sql ); 

        $this->pdoQueryOk( $dbh , $sql );



	}

	function testMysql()
	{
		$builder = new SchemaSqlBuilder('mysql');
		ok( $builder );

	}
}

