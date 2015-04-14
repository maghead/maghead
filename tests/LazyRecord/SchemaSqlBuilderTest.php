<?php
use LazyRecord\Sqlbuilder\SqlBuilder;
use LazyRecord\Connection;
use LazyRecord\Testing\BaseTestCase;

class SqlBuilderTest extends PHPUnit_Framework_TestCase
{






    public function pdoQueryOk($dbh,$sql)
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

    public function schemaProvider()
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
    public function testBuilder($schema) {
        $this->insertIntoDataSource('mysql',$schema);
        $this->insertIntoDataSource('sqlite',$schema);
        $this->insertIntoDataSource('pgsql',$schema);
    }

    public function insertIntoDataSource($driverType,$schema)
    {
        $connManager = LazyRecord\ConnectionManager::getInstance();
        $connManager->free();

        $dataSource = BaseTestCase::createDataSourceConfig($driverType);
        $connManager->addDataSource($driverType, $dataSource);

        $pdo = $connManager->getConnection($driverType);
        $this->assertInstanceOf('PDO', $pdo);

        $queryDriver = $connManager->getQueryDriver($driverType);
        $builder = SqlBuilder::create($queryDriver,array( 'rebuild' => true ));
        $builder->build($schema);

        $sqls = $builder->build( $schema );
        $this->assertNotEmpty($sqls);
        foreach ($sqls as $sql) {
            $this->pdoQueryOk( $pdo, $sql );
        }
    }
}

