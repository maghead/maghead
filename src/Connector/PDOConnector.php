<?php

namespace Maghead\Connector;

use Maghead\Connection;
use PDO;

class PDOConnector
{
    public static $defaultOptions = [
        PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT               => true,
    ];

    public static function connect(array $config)
    {
        $c = new Connection($config['dsn'], $config['user'], $config['pass'], static::$defaultOptions);
        $c->config = $config;
        return $c;
    }
}
