<?php

namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;

class ConfigPreprocessorTest extends TestCase
{


    public function nodeConfigProvider()
    {
        $data = [];
        $data[] = [
            [ 'dsn' => 'mysql:host=localhost;dbname=testing' ],
            [
                'dsn' => 'mysql:host=localhost;dbname=testing',
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'testing',
                'user' => null,
                'password' => null,
                'query_options' => [],
                'connection_options' => [
                    1002 => 'SET NAMES utf8',
                ],
            ]
        ];


        $data[] = [
            [ 'dsn' => 'mysql:host=localhost;dbname=testing', 'user' => 'root', 'password' => 'root12341234' ],
            [
                'dsn' => 'mysql:host=localhost;dbname=testing',
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'testing',
                'user' => 'root',
                'password' => 'root12341234',
                'query_options' => [],
                'connection_options' => [
                    1002 => 'SET NAMES utf8',
                ],
            ]
        ];

        $data[] = [
            [ 'dsn' => 'sqlite::memory:', ],
            [
                'dsn' => 'sqlite::memory:',
                'driver' => 'sqlite',
                'user' => null,
                'password' => null,
                'query_options' => [],
                'connection_options' => [],
            ]
        ];

        $data[] = [
            ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root'],
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'user' => 'root',
                'password' => null,
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'dsn' => 'mysql:host=localhost',
            ]
        ];

        $data[] = [
            ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'port' => 3306],
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'user' => 'root',
                'port' => 3306,
                'password' => null,
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'dsn' => 'mysql:host=localhost;port=3306',
            ]
        ];

        $data[] = [
            ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'unix_socket' => '/opt/local/var/run/mysql56/mysqld.sock'],
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'user' => 'root',
                'password' => null,
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'unix_socket' => '/opt/local/var/run/mysql56/mysqld.sock',
                'dsn' => 'mysql:unix_socket=/opt/local/var/run/mysql56/mysqld.sock',
            ]
        ];

        // socket should be renamed to "unix_socket"
        $data[] = [
            ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'socket' => '/opt/local/var/run/mysql56/mysqld.sock'],
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'user' => 'root',
                'password' => null,
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'unix_socket' => '/opt/local/var/run/mysql56/mysqld.sock',
                'dsn' => 'mysql:unix_socket=/opt/local/var/run/mysql56/mysqld.sock',
            ]
        ];


        // dbname should be renamed to 'database'
        $data[] = [
            ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'dbname' => 'testing'],
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'user' => 'root',
                'password' => null,
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'dsn' => 'mysql:host=localhost;dbname=testing',
                'database' => 'testing'
            ]
        ];


        // 'pass' should be renamed to 'password'
        $data[] = [
            ['driver' => 'mysql', 'host' => 'localhost', 'user' => 'root', 'database' => 'testing', 'pass' => 'testing'],
            [
                'driver' => 'mysql',
                'host' => 'localhost',
                'user' => 'root',
                'password' => 'testing',
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'dsn' => 'mysql:host=localhost;dbname=testing',
                'database' => 'testing'
            ]
        ];

        $data[] = [
            [   'driver' => 'mysql',
                'user' => 'root',
                'database' => 'testing',
                'write' => ['192.168.0.1'],
                'read' => ['192.168.0.2', '192.168.0.3'],
            ],
            [
                'driver' => 'mysql',
                'user' => 'root',
                'password' => null,
                'query_options' => [],
                'connection_options' => [ 1002 => 'SET NAMES utf8' ],
                'database' => 'testing',
                'write' => [
                    [
                        'driver' => 'mysql',
                        'user' => 'root',
                        'database' => 'testing',
                        'password' => null,
                        'query_options' => Array(),
                        'connection_options' => Array(1002 => 'SET NAMES utf8'),
                        'host' => '192.168.0.1',
                        'dsn' => 'mysql:host=192.168.0.1;dbname=testing',
                    ]
                ],
                'read' => [
                    0 => [
                        'driver' => 'mysql',
                        'user' => 'root',
                        'database' => 'testing',
                        'password' => null,
                        'query_options' => Array(),
                        'connection_options' => Array(1002 => 'SET NAMES utf8'),
                        'host' => '192.168.0.2',
                        'dsn' => 'mysql:host=192.168.0.2;dbname=testing',
                    ],
                    1 => [
                        'driver' => 'mysql',
                        'user' => 'root',
                        'database' => 'testing',
                        'password' => null,
                        'query_options' => Array (),
                        'connection_options' => Array(1002 => 'SET NAMES utf8'),
                        'host' => '192.168.0.3',
                        'dsn' => 'mysql:host=192.168.0.3;dbname=testing',
                    ],
                ],
            ]
        ];




        return $data;
    }


    /**
     * @dataProvider nodeConfigProvider
     */
    public function testNormalizeNodeConfig($input, $expect)
    {
        $node = ConfigPreprocessor::normalizeNodeConfig($input);
        $this->assertEquals($expect, $node);
    }
}
