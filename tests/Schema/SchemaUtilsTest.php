<?php
use Maghead\ConfigLoader;
use Maghead\Bootstrap;
use Maghead\Schema\SchemaUtils;

class SchemaUtilsTest extends PHPUnit_Framework_TestCase
{
    public $config;

    public $loader;

    public function setUp()
    {
        $this->loader = new ConfigLoader;
        $this->config = $this->loader->loadFromSymbol(true); // force loading

        $bootstrap = new Bootstrap($this->config);
        $bootstrap->init();
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



