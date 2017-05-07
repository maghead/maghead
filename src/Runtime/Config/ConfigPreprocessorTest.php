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
