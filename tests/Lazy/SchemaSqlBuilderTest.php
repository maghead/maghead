<?php
use Lazy\SchemaSqlBuilder;

class SchemaSqlBuilderTest extends PHPUnit_Framework_TestCase
{

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

		$dbh = new PDO('sqlite:tests.db'); // success
		$builder = new SchemaSqlBuilder('sqlite');
		ok( $builder );

		$s = new \tests\AuthorSchema;
		$authorbook = new \tests\AuthorBookSchema;
		$bookschema = new \tests\BookSchema;
		ok( $s );

		$sqls = $builder->build($s);
		ok( $sqls );

        // var_dump( $sql ); 
        foreach( $sqls as $sql ) 
            $this->pdoQueryOk( $dbh , $sql );


		ok( $authorbook );
		$sqls = $builder->build($authorbook);
		ok( $sqls );
        // var_dump( $sql ); 

        foreach( $sqls as $sql )
            $this->pdoQueryOk( $dbh , $sql );


		ok( $bookschema );
		$sqls = $builder->build($bookschema);
		ok( $sqls );
        // var_dump( $sql ); 

        foreach( $sqls as $sql )
            $this->pdoQueryOk( $dbh , $sql );
	}


	function testMysql()
	{
		$builder = new SchemaSqlBuilder('mysql');
		ok( $builder );

        $pdo = new PDO( 
            'mysql:host=localhost;dbname=lazy_tests', 
            'root', 
            '123123', 
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") 
        ); 
        ok( $pdo , 'pdo connection' );

        $this->pdoQueryOk( $pdo, 'drop TABLE IF EXISTS authors' );
        $this->pdoQueryOk( $pdo, 'drop TABLE IF EXISTS author_books' );
        $this->pdoQueryOk( $pdo, 'drop TABLE IF EXISTS books' );

		$authorschema = new \tests\AuthorSchema;
		$authorbookschema = new \tests\AuthorBookSchema;
		$bookschema = new \tests\BookSchema;
        ok( $authorschema );
        ok( $authorbookschema );
        ok( $bookschema );

        ok( $sqls = $builder->build( $authorschema ) );
        foreach( $sqls as $sql )
            $this->pdoQueryOk( $pdo, $sql );

        ok( $sqls = $builder->build( $bookschema ) );
        foreach( $sqls as $sql )
            $this->pdoQueryOk( $pdo, $sql );

        ok( $sqls = $builder->build( $authorbookschema ) );
        foreach( $sqls as $sql )
            $this->pdoQueryOk( $pdo, $sql );

	}
}

