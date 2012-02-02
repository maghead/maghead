<?php
use LazyRecord\SchemaSqlBuilder;

class SchemaSqlBuilderTest extends PHPUnit_Framework_TestCase
{

	function testSqlite()
	{
		$builder = new SchemaSqlBuilder('sqlite');
		ok( $builder );

		$s = new \tests\AuthorSchema;
		$s->build();
		ok( $s );

		$sql = $builder->build($s);
		ok( $sql );

		var_dump( $sql ); 

		if( file_exists('tests.db') ) {
			unlink('tests.db');
		}

		$dbh = new PDO('sqlite:tests.db'); // success
		$ret = $dbh->query( $sql );
		ok( $ret );

		$error = $dbh->errorInfo();
		if($error[1] != null ) {
			throw new Exception('DATABASE CONNECTION ERROR');
		}


	}

	function testMysql()
	{
		$builder = new SchemaSqlBuilder('mysql');
		ok( $builder );

	}
}

