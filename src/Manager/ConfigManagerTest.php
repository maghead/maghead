<?php

namespace Maghead\Manager;

use Maghead\Runtime\Config\Config;
use Maghead\Runtime\Config\FileConfigLoader;
use Maghead\Testing\TestCase;

/**
 * @group manager
 */
class ConfigManagerTest extends TestCase
{
    const TEST_CONFIG = 'tests/config/.database.config.yml';

    public function setUp()
    {
        parent::setUp();
        copy('tests/config/database.yml', self::TEST_CONFIG);
    }

    public function tearDown()
    {
        parent::tearDown();
        if (file_exists(self::TEST_CONFIG)) {
            unlink(self::TEST_CONFIG);
        }
    }

    public function testRemoveNode()
    {
        $manager = new ConfigManager(FileConfigLoader::load(self::TEST_CONFIG, true));
        $manager->removeDatabase('sqlite');
        $manager->removeDatabase('mysql');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);

        $this->assertFileEquals('tests/fixtures/config/testRemoveNode.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithOptions()
    {
        $manager = new ConfigManager(FileConfigLoader::load(self::TEST_CONFIG, true));
        $manager->addDatabase('shard1', 'mysql', [
            'host' => 'localhost',
            'dbname' => 'shard1',
            'user' => 'c9s',
            'password' => '12341234',
        ]);
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/fixtures/config/testAddNodeWithOptions.expected', self::TEST_CONFIG);
    }

    public function testAddNodeWithoutOptions()
    {
        $manager = new ConfigManager(FileConfigLoader::load(self::TEST_CONFIG, true));
        $manager->addDatabase('shard1', 'mysql:host=localhost;dbname=shard1');
        $manager->addDatabase('shard2', 'mysql:host=localhost;dbname=shard2');
        $ret = $manager->save(self::TEST_CONFIG);
        $this->assertTrue($ret);
        $this->assertFileEquals('tests/fixtures/config/testAddNodeWithoutOptions.expected', self::TEST_CONFIG);
    }
}
