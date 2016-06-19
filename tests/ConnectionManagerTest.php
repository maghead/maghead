<?php
use LazyRecord\Connection;
use LazyRecord\ConnectionManager;
use LazyRecord\ConfigLoader;
use SQLBuilder\Driver\PDOMySQLDriver;

class ConnectionManagerTest extends PHPUnit_Framework_TestCase
{


    public function testDefaultDataSource()
    {
        if (getenv('DB') != 'mysql') {
            return $this->markTestSkipped('require mysql to test');
        }
        $configLoader = ConfigLoader::getInstance();
        $configLoader->loadFromSymbol(true);
        $configLoader->setDefaultDataSourceId('mysql');
        $this->assertEquals('mysql',$configLoader->getDefaultDataSourceId());

        $connManager = ConnectionManager::getInstance();
        $connManager->init($configLoader);
        $this->assertEquals('mysql',$configLoader->getDefaultDataSourceId());

        $conn = $connManager->getConnection('default');
        $queryDriver = $conn->createQueryDriver();
        $this->assertInstanceOf('SQLBuilder\Driver\PDOMySQLDriver', $queryDriver);
        $connManager->free();
    }


    /*
    public function testConnectionManager()
    {
        // $pdo = new PDO( 'sqlite3::memory:', null, null, array(PDO::ATTR_PERSISTENT => true) );
        $conn = new Connection('sqlite::memory:');
        $manager = LazyRecord\ConnectionManager::getInstance();
        $manager->free();
        $manager->add($conn, 'master');
        $manager->addDataSource( 'master', array(
            'dsn' => 'sqlite::memory:',
            'user' => null,
            'pass' => null,
            'options' => array(),
        ));

        $master = $manager->getConnection('master');
        $this->assertInstanceOf('LazyRecord\Connection',$master);

        // array access test.
        ok($manager['master']);
        $manager->free();
    }
    */
}


