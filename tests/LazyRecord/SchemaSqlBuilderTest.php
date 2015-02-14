<?php
use LazyRecord\Sqlbuilder\SqlBuilder;

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
            array( new \AuthorBooks\Model\AuthorSchema ),
            array( new \AuthorBooks\Model\AddressSchema ),
            array( new \AuthorBooks\Model\AuthorBookSchema),
            array( new \AuthorBooks\Model\BookSchema ),
            array( new \TestApp\Model\NameSchema ),
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
        ok($pdo , 'pdo connection');
        $this->assertInstanceOf('PDO', $pdo);

        $queryDriver = $connManager->getQueryDriver($dataSource);
        ok($queryDriver);

        $builder = SqlBuilder::create($queryDriver,array( 'rebuild' => true ));
        ok( $builder );

        $builder->build($schema);

        ok( $sqls = $builder->build( $schema ) );
        foreach( $sqls as $sql ) {
            $this->pdoQueryOk( $pdo, $sql );
        }
    }
}

