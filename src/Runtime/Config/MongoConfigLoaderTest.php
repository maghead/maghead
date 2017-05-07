<?php

namespace Maghead\Runtime\Config;

use MongoDB\Client;
use PHPUnit\Framework\TestCase;

class MongoConfigLoaderTest extends TestCase
{
    public function testDefaultMongoConfigLoader()
    {
        if (!extension_loaded('mongodb')) {
            $this->markTestSkipped('this test requires mongodb');
        }

        $client = new \MongoDB\Client("mongodb://localhost:27017");

        $collection = $client->maghead->configs;
        $result = $collection->deleteMany([]);
        $this->assertTrue($result->isAcknowledged());

        $config = FileConfigLoader::load('tests/config/mysql_configserver.yml');


        $result = $collection->insertOne([
            'appId' => 'testapp',
            'stash' => $config->stash,
        ]);

        $this->assertTrue($result->isAcknowledged());
        $this->assertNotNull($result->getInsertedId());

        $config = MongoConfigLoader::load('testapp', $client);
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }
}
