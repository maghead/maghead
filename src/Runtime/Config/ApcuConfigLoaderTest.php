<?php

namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;

class ApcuConfigLoaderTest extends TestCase
{
    public function setUp() {
        parent::setUp();
        if (!extension_loaded('apcu')) {
            return $this->markTestSkipped('require apcu extension.');
        }
    }

    public function testApcuConfigLoaderFallback()
    {
        $config = ApcuConfigLoader::load("testapp", function() {
            return FileConfigLoader::load('tests/config/mysql.yml');
        });
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }

    public function testApcuConfigLoaderSecondFallback()
    {
        $config = ApcuConfigLoader::load("testapp", function() {
            return false;
        }, function() {
            return FileConfigLoader::load('tests/config/mysql.yml');
        });
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }
}
