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
        if (file_exists(self::TEST_CONFIG)) {
            unlink(self::TEST_CONFIG);
        }
    }

    public function testSetDefaultNode()
    {
        $loader = new ConfigLoader;
        $config = $loader->loadFromFile(self::TEST_CONFIG);
        $manager = new ConfigManager($config);
        $manager->setDefaultNode('mysql');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/config/testSetDefaultNode.expected', self::TEST_CONFIG);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetDefaultInexistNode()
    {
        $loader = new ConfigLoader;
        $config = $loader->loadFromFile(self::TEST_CONFIG);
        $manager = new ConfigManager($config);
        $manager->setDefaultNode('foo');
    }

    public function testRemoveNode()
    {
        $loader = new ConfigLoader;
        $config = $loader->loadFromFile(self::TEST_CONFIG);
        $manager = new ConfigManager($config);
        $manager->removeNode('sqlite');
        $manager->removeNode('mysql');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/config/testRemoveNode.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithOptions()
    {
        $loader = new ConfigLoader;
        $config = $loader->loadFromFile(self::TEST_CONFIG);
        $manager = new ConfigManager($config);
        $manager->addNode('shard1', 'mysql', [
            'host' => 'localhost',
            'dbname' => 'shard1',
            'user' => 'c9s',
            'password' => '12341234',
        ]);
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/config/testAddNodeWithOptions.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithoutOptions()
    {
        $loader = new ConfigLoader;
        $config = $loader->loadFromFile(self::TEST_CONFIG);
        $manager = new ConfigManager($config);
        $manager->addNode('shard1', 'mysql:host=localhost;dbname=shard1');
        $manager->addNode('shard2', 'mysql:host=localhost;dbname=shard2');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/config/testAddNodeWithoutOptions.expected', self::TEST_CONFIG);
    }
}
