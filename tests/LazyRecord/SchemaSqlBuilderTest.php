<?php
use LazyRecord\SchemaSqlBuilder;

class SchemaSqlBuilderTest extends PHPUnit_Framework_TestCase
{

	function testSqlite()
	{
		$builder = new SchemaSqlBuilder('sqlite');
		ok( $builder );
	}

	function testMysql()
	{
		$builder = new SchemaSqlBuilder('mysql');
		ok( $builder );

		$s = new \tests\AuthorSchema;
		ok( $s );

	}
}

