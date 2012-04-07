<?php
use LazyRecord\Schema\SqlBuilder;

class SqlBuilderTest extends PHPUnit_Framework_TestCase
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



    function schemaProvider()
    {
        return array(
            array( new \tests\AuthorSchema ),
            array( new \tests\AddressSchema ),
            array( new \tests\AuthorBookSchema),
            array( new \tests\BookSchema ),
            array( new \tests\NameSchema ),
        );
    }



    /**
     * @dataProvider schemaProvider
     */
	function testMysql($schema)
	{
        $connManager = LazyRecord\ConnectionManager::getInstance();

        if( ! isset($connManager['mysql']) )
            return;

        $pdo = $connManager->getConnection('mysql');
        ok( $pdo , 'pdo connection' );
        $builder = new SqlBuilder($connManager->getQueryDriver('mysql') , array( 
            'rebuild' => true,
        ));
		ok( $builder );
        ok( $sqls = $builder->build( $schema ) );
        foreach( $sqls as $sql ) {
            $this->pdoQueryOk( $pdo, $sql );
        }
	}


    /**
     * @dataProvider schemaProvider
     */
	function testSqlite($schema)
	{
        $connManager = LazyRecord\ConnectionManager::getInstance();
        $pdo = $connManager->getConnection('sqlite');
        ok( $pdo , 'pdo connection' );
        $builder = new SqlBuilder($connManager->getQueryDriver('sqlite') , array( 
            'rebuild' => true,
        ));
		ok( $builder );
        ok( $sqls = $builder->build( $schema ) );
        foreach( $sqls as $sql ) {
            $this->pdoQueryOk( $pdo, $sql );
        }
	}


}

