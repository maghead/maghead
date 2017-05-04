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
}

