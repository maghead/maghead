<?php
use LazyRecord\Schema\SqlBuilder;

class SqlBuilderTest extends PHPUnit_Framework_TestCase
{
    function setup()
    {
#          $connm->addDataSource('mysql', array( 
#              'dsn' => 'mysql:host=localhost;dbname=lazy_test',
#              'user' => 'root',
#              'pass' => '123123',
#              'connection_options' => array(
#                  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
#              ) 
#          ));
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
		$dbh = LazyRecord\ConnectionManager::getInstance()->getConnection('default');
        $builder = new SqlBuilder(LazyRecord\ConnectionManager::getInstance()->getQueryDriver('default'), array(
            'rebuild' => true,
        ));
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
        $connManager = LazyRecord\ConnectionManager::getInstance();
        $pdo = $connManager->getConnection('mysql');
        ok( $pdo , 'pdo connection' );

        $builder = new SqlBuilder($connManager->getQueryDriver('mysql') , array( 
            'rebuild' => true,
        ));
		ok( $builder );

        $schemas = array();
		$schemas[] = new \tests\AuthorSchema;
		$schemas[] = new \tests\AuthorBookSchema;
		$schemas[] = new \tests\BookSchema;
        foreach( $schemas as $schema ) {
            ok( $sqls = $builder->build( $schema ) );
            foreach( $sqls as $sql ) {
                $this->pdoQueryOk( $pdo, $sql );
            }
        }
	}
}

