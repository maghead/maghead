<?php

namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;

use Exception;

class ArrayConfigLoaderTest extends TestCase
{
    public function testEmptyConfig()
    {
        $config = ArrayConfigLoader::load([]);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }

    public function testSimpleDatabasesConfig()
    {
        $config = ArrayConfigLoader::load([
            'databases' => [
                'master' => [
                    'dsn' => 'mysql:host=localhost;dbname=testing',
                    'user' => 'root',
                ],
            ],
        ]);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }
}

