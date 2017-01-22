Connections
===========

To get connection manager:

```php
$conn = Maghead\ConnectionManager::getInstance();
```

To get default connection:

```php
$defaultConnection = $conn->getDefaultConnection();  // PDO connection object
```

To get connection with data source ID:

```php
$pdo = \Maghead\ConnectionManager::getInstance()->getConnection('default');
```

To add a new data source

```php
$conn->addDataSource( 'master', array( 
    'dsn' => 'sqlite::memory:',
    'user' => null,
    'pass' => null,
    'options' => array(),
));
```

ConnectionManager is `ArrayAccess` implemented, so you can use ['key'] to get connection:

```php
$conn = Maghead\ConnectionManager::getInstance();
$conn['default']
```

To close connections:

```
$conn->free();
```

