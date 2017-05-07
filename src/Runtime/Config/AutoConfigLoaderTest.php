<?php
namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;

class AutoConfigLoaderTest extends TestCase
{
    public function testAutoLoader()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('this test requires mongodb');
        }
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped('this test requires apcu');
        }

        $config = AutoConfigLoader::load('autotest', 'tests/config/mysql_configserver.yml', 1);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }
}



