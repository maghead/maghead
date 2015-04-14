<?php
use LazyRecord\Sqlbuilder\SqlBuilder;
use LazyRecord\Connection;

class SqlBuilderTest extends PHPUnit_Framework_TestCase
{

    public function getDSN($driver)
    {
        if ($dsn = getenv('DB_' . strtoupper($driver) .  '_DSN')) {
            return $dsn;
        }
    }

    public function getDatabaseName($driver) 
    {
        if ($name = getenv('DB_' . strtoupper($driver) .  '_NAME')) {
            return $name;
        }
    }

    public function getDatabaseUser($driver)
    {
        if ($user = getenv('DB_' . strtoupper($driver) . '_USER')) {
            return $user;
        }
    }

    public function getDatabasePassword($driver) 
    {
        if ($pass = getenv('DB_' . strtoupper($driver) . '_PASS')) {
            return $pass;
        }
    }

    public function createDataSourceConfig($driver) {
        if ($dsn = $this->getDSN($driver)) {
            $config = array('dsn' => $dsn);
            $user = $this->getDatabaseUser($driver);
            $pass = $this->getDatabasePassword($driver);
            $config['user'] = $user;
            $config['pass'] = $pass;
            return $config;
        } else if ( $this->getDatabaseName($driver) ) {
            return [
                'driver' => $driver,
                'database'  => $this->getDatabaseName($driver),
                'user' => $this->getDatabaseUser($driver),
                'pass' => $this->getDatabasePassword($driver),
            ];
        }
    }





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
        $dataSource = $this->createDataSourceConfig($driverType);
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

