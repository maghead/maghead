<?php
use Lazy\Schema\SqlBuilder;

class SqlBuilderTest extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        Lazy\QueryDriver::free();
        $connm = Lazy\ConnectionManager::getInstance();
        $connm->free();

        $connm->addDataSource('sqlite', array( 
            'dsn' => 'sqlite::memory:'
        ));

        $connm->addDataSource('mysql', array( 
            'dsn' => 'mysql:host=localhost;dbname=lazy_tests',
            'user' => 'root',
            'pass' => '123123',
            'connection_options' => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ) 
        ));
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
		$dbh = Lazy\ConnectionManager::getInstance()->getConnection('sqlite');
		$builder = new SqlBuilder('sqlite', Lazy\ConnectionManager::getInstance()->getQueryDriver('sqlite') );
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
        $connManager = Lazy\ConnectionManager::getInstance();
        $pdo = $connManager->getConnection('mysql');
        ok( $pdo , 'pdo connection' );

		$builder = new SqlBuilder('mysql', $connManager->getQueryDriver('mysql') );
		ok( $builder );

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

