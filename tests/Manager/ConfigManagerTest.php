<?php
use Maghead\Manager\ConfigManager;
use Maghead\Config;
use Maghead\ConfigLoader;

class ConfigManagerTest extends PHPUnit_Framework_TestCase
{
    const TEST_CONFIG = 'tests/.database.config.yml';

    public function setUp()
    {
        copy('tests/database.yml', self::TEST_CONFIG);
        parent::setUp();
    }

    public function tearDown()
    {
    }

    public function testAddNodeWithoutOptions()
    {

        $loader = new ConfigLoader;
        $config = $loader->loadFromFile(self::TEST_CONFIG);
        // var_dump($config);

        $manager = new ConfigManager($config);
        $manager->addNode('shard1', 'mysql:host=localhost;dbname=shard1');
        $manager->addNode('shard2', 'mysql:host=localhost;dbname=shard2');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/config/testAddNodeWithoutOptions.expected', self::TEST_CONFIG);
    }
}
