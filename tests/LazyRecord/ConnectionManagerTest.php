<?php

class ConnectionManagerTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        // $pdo = new PDO( 'sqlite3::memory:', null, null, array(PDO::ATTR_PERSISTENT => true) );
        $conn = new PDO( 'sqlite::memory:' );
        ok( $conn );

        $manager = LazyRecord\ConnectionManager::getInstance();
        ok( $manager );

        $manager->add($conn, 'default');

        $conn = $manager->getDefault();
        ok( $conn );

        $manager->addDataSource( 'master', array( 
            'dsn' => 'sqlite::memory',
            'user' => null,
            'pass' => null,
            'options' => array(),
        ));

        $master = $manager->getConnection('master');
        ok( $master );
    }
}


