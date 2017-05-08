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

        $client = new Client("mongodb://localhost:27017");

        $result = MongoConfigWriter::removeById($client, 'testapp');
        $this->assertTrue($result->isAcknowledged());

        $config = FileConfigLoader::load('tests/config/mysql_configserver.yml');

        $result = MongoConfigWriter::write($client, $config);
        $this->assertTrue($result->isAcknowledged());
        // $this->assertNotNull($result->getInsertedId());

        $config = MongoConfigLoader::load($client, 'testapp');
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);

        $result = MongoConfigWriter::removeById($client, 'testapp');
        $this->assertTrue($result->isAcknowledged());
    }
}
