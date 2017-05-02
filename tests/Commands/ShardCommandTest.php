<?php

use CLIFramework\Testing\CommandTestCase;
use Maghead\Console;

/**
 * @group command
 */
class ShardCommandsTest extends CommandTestCase
{
    public function setupApplication()
    {
        return new Console;
    }

    public function setUp()
    {
        parent::setUp();
        $db = getenv('DB') ?: "sqlite";
        copy("tests/config/$db.yml", "tests/config/tmp.yml");
        $this->app->run(['maghead','use','tests/config/tmp.yml']);
        if ($db !== "mysql") {
            return $this->markTestSkipped('sqlite migration is not supported.');
        }
    }

    public function testShardMappingCreate()
    {
        $this->app->run(['maghead', 'shard',
            'mapping', 'add', '--hash', '-s', 's1', '-s', 's2', '--key', 'store_id', 'store_key'
        ]);

        $this->app->run(['maghead', 'shard',
            'mapping', 'remove', 'store_key'
        ]);
    }


    public function testShardAllocate()
    {
        $this->app->run(['maghead', 'shard',
            'mapping', 'add', '--hash', '-s', 's1', '-s', 's2', '--key', 'store_id', 'store_key'
        ]);

        $this->app->run(['maghead', 'shard', 'allocate', '--mapping', 'store_key', '--instance', 'local', 'a11']);

        $this->app->run(['maghead', 'shard',
            'mapping', 'remove', 'store_key'
        ]);
    }

    public function testShardClone()
    {
        $this->expectOutputRegex('/Copying TABLE/');

        $this->app->run(['maghead', 'shard',
            'mapping', 'add', '--hash', '-s', 's1', '-s', 's2', '--key', 'store_id', 'store_key'
        ]);

        $this->app->run(['maghead', 'shard', 'clone', '--mapping', 'store_key', '--instance', 'local', 'master', 'a11']);

        $this->app->run(['maghead', 'shard',
            'mapping', 'remove', 'store_key'
        ]);
    }
}
