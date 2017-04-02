<?php
namespace Maghead\Connector;
use Maghead\Connection;
use Maghead\DSN\DSNParser;
use PDO;

/*

- ATTR_STATEMENT_CLASS
- ATTR_AUTOCOMMIT
- ATTR_DEFAULT_FETCH_MODE

Other MYSQL specific available options are:

enum {
    PDO_MYSQL_ATTR_USE_BUFFERED_QUERY = PDO_ATTR_DRIVER_SPECIFIC,
    PDO_MYSQL_ATTR_LOCAL_INFILE,
    PDO_MYSQL_ATTR_INIT_COMMAND,
#ifndef PDO_USE_MYSQLND
    PDO_MYSQL_ATTR_READ_DEFAULT_FILE,
    PDO_MYSQL_ATTR_READ_DEFAULT_GROUP,
    PDO_MYSQL_ATTR_MAX_BUFFER_SIZE,
#endif
    PDO_MYSQL_ATTR_COMPRESS,
    PDO_MYSQL_ATTR_DIRECT_QUERY,
    PDO_MYSQL_ATTR_FOUND_ROWS,
    PDO_MYSQL_ATTR_IGNORE_SPACE,
    PDO_MYSQL_ATTR_SSL_KEY,
    PDO_MYSQL_ATTR_SSL_CERT,
    PDO_MYSQL_ATTR_SSL_CA,
    PDO_MYSQL_ATTR_SSL_CAPATH,
    PDO_MYSQL_ATTR_SSL_CIPHER,
#if MYSQL_VERSION_ID > 50605 || defined(PDO_USE_MYSQLND)
    PDO_MYSQL_ATTR_SERVER_PUBLIC_KEY,
#endif
    PDO_MYSQL_ATTR_MULTI_STATEMENTS,
};
*/

class PDOMySQLConnector
{
    public static $defaultOptions = [
        PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
        // PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT               => true,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES utf8',
    ];


    public static function connect($dsn, $user, $pass)
    {
        return new Connection($dsn, $user, $pass, static::$defaultOptions);
    }
}
