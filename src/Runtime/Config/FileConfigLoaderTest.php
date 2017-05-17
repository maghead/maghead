<?php

namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;

class FileConfigLoaderTest extends TestCase
{
    public function testLoadSimpleFile()
    {
        $config = FileConfigLoader::load('tests/config/mysql.yml');
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }

    /**
     * @depends testLoadSimpleFile
     */
    public function testLoadFromPhpFormatFile()
    {
        FileConfigLoader::compile('tests/config/mysql.yml');
        $config = FileConfigLoader::load('tests/config/mysql.php');
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }
}

