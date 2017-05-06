<?php

namespace Maghead\Connector;

use Maghead\Runtime\Connection;
use PDO;

class PDOConnector
{
    public static $defaultOptions = [
        PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT               => true,
    ];

    public static function connect(array $config)
    {
        if ($config['driver'] === 'mysql') {
            return PDOMySQLConnector::connect($config['dsn'], $config['user'], $config['password']);
        }
        $connection = new Connection($config['dsn'], $config['user'], $config['password'], $config['connection_options']);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // TODO: can we make this optional ?
        return $connection;
    }
}
