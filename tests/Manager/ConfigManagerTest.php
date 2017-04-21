<?php
use Maghead\Manager\ConfigManager;
use Maghead\Config;
use Maghead\ConfigLoader;

/**
 * @group manager
 */
class ConfigManagerTest extends PHPUnit\Framework\TestCase
{
    const TEST_CONFIG = 'tests/config/.database.config.yml';

    public function setUp()
    {
        copy('tests/config/database.yml', self::TEST_CONFIG);
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
        $config = ConfigLoader::loadFromFile(self::TEST_CONFIG, true);
        $manager = new ConfigManager($config);
        $manager->setMasterNode('mysql');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testSetDefaultNode.expected');
        $this->assertFileEquals('tests/fixtures/config/testSetDefaultNode.expected', self::TEST_CONFIG);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetDefaultInexistNode()
    {
        $manager = new ConfigManager(self::TEST_CONFIG);
        $manager->setMasterNode('foo');
    }

    public function testRemoveNode()
    {
        $manager = new ConfigManager(self::TEST_CONFIG);
        $manager->removeNode('sqlite');
        $manager->removeNode('mysql');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testRemoveNode.expected');
        $this->assertFileEquals('tests/fixtures/config/testRemoveNode.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithOptions()
    {
        $manager = new ConfigManager(self::TEST_CONFIG);
        $manager->addNode('shard1', 'mysql', [
            'host' => 'localhost',
            'dbname' => 'shard1',
            'user' => 'c9s',
            'password' => '12341234',
        ]);
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testAddNodeWithOptions.expected');
        $this->assertFileEquals('tests/fixtures/config/testAddNodeWithOptions.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithoutOptions()
    {
        $config = ConfigLoader::loadFromFile(self::TEST_CONFIG, true);
        $manager = new ConfigManager($config);
        $manager->addNode('shard1', 'mysql:host=localhost;dbname=shard1');
        $manager->addNode('shard2', 'mysql:host=localhost;dbname=shard2');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testAddNodeWithoutOptions.expected');
        $this->assertFileEquals('tests/fixtures/config/testAddNodeWithoutOptions.expected', self::TEST_CONFIG);
    }
}
