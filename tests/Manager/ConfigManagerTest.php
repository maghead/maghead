<?php
use Maghead\Manager\ConfigManager;
use Maghead\Runtime\Config\Config;
use Maghead\Runtime\Config\FileConfigLoader;

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

    public function testRemoveNode()
    {
        $manager = new ConfigManager(self::TEST_CONFIG);
        $manager->removeDatabase('sqlite');
        $manager->removeDatabase('mysql');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testRemoveNode.expected');
        $this->assertFileEquals('tests/fixtures/config/testRemoveNode.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithOptions()
    {
        $manager = new ConfigManager(self::TEST_CONFIG);
        $manager->addDatabase('shard1', 'mysql', [
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
        $config = FileConfigLoader::load(self::TEST_CONFIG, true);
        $manager = new ConfigManager($config);
        $manager->addDatabase('shard1', 'mysql:host=localhost;dbname=shard1');
        $manager->addDatabase('shard2', 'mysql:host=localhost;dbname=shard2');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        // copy(self::TEST_CONFIG, 'tests/fixtures/config/testAddNodeWithoutOptions.expected');
        $this->assertFileEquals('tests/fixtures/config/testAddNodeWithoutOptions.expected', self::TEST_CONFIG);
    }
}
