<?php
use LazyRecord\Sqlbuilder\SqlBuilderFactory;

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
        $this->insertIntoDataSource('pgsql',$schema);
    }

    function insertIntoDataSource($dataSource,$schema)
    {
        $connManager = LazyRecord\ConnectionManager::getInstance();
        if( ! $connManager->hasDataSource($dataSource) )
            return;

        $pdo = $connManager->getConnection($dataSource);
        ok( $pdo , 'pdo connection' );

        $queryDriver = $connManager->getQueryDriver($dataSource);
        ok( $queryDriver );

        $builder = SqlBuilderFactory::create($queryDriver,array( 'rebuild' => true ));
        ok( $builder );

        $builder->build($schema);

        ok( $sqls = $builder->build( $schema ) );
        foreach( $sqls as $sql ) {
            $this->pdoQueryOk( $pdo, $sql );
        }
    }
}

