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
    function testBuilder($schema) {
        $this->insertIntoDataSource('mysql',$schema);
        $this->insertIntoDataSource('sqlite',$schema);
    }

    function insertIntoDataSource($dataSource,$schema)
    {
        $connManager = LazyRecord\ConnectionManager::getInstance();
        if( ! $connManager->hasDataSource($dataSource) )
            return;

        $pdo = $connManager->getConnection($dataSource);
        ok( $pdo , 'pdo connection' );
        $builder = new SqlBuilder($connManager->getQueryDriver($dataSource) , array( 
            'rebuild' => true,
        ));
        ok( $builder );
        ok( $sqls = $builder->build( $schema ) );
        foreach( $sqls as $sql ) {
            $this->pdoQueryOk( $pdo, $sql );
        }
    }
}

