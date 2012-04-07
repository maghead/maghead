Connections
===========

To get connection manager:

    $conn = LazyRecord\ConnectionManager::getInstance();

To get default connection:

    $defaultConnection = $conn->getDefault();  // PDO connection object

To get connection with data source ID:

    $pdo = \LazyRecord\ConnectionManager::getInstance()->getConnection('default');

To add a new data source

    $conn->addDataSource( 'master', array( 
        'dsn' => 'sqlite::memory:',
        'user' => null,
        'pass' => null,
        'options' => array(),
    ));

ConnectionManager is `ArrayAccess` implemented, so you can use ['key'] to get connection:

    $conn = LazyRecord\ConnectionManager::getInstance();
    $conn['default']

To close connections:

    $conn->free();

