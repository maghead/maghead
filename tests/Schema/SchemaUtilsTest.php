<?php
use Maghead\ConfigLoader;
use Maghead\Bootstrap;
use Maghead\Schema\SchemaUtils;

/**
 * @group schema
 */
class SchemaUtilsTest extends PHPUnit_Framework_TestCase
{
    public $config;

    public function setUp()
    {
        $this->config = ConfigLoader::loadFromSymbol(true); // force loading
        Bootstrap::setup($this->config);
    }

    public function testFindSchemasByClassNames()
    {
        $paths = $this->config->getSchemaPaths();
        $this->assertNotEmpty($paths);
        $this->assertTrue(is_array($paths));
        $schemas = SchemaUtils::findSchemasByArguments($this->config, array('TestApp\\Model\\UserSchema'));
        $this->assertNotEmpty($paths);
    }
}
