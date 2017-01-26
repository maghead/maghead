<?php
use Maghead\ConfigLoader;
use Maghead\Bootstrap;
use Maghead\Schema\SchemaUtils;

class SchemaUtilsTest extends PHPUnit_Framework_TestCase
{
    public $loader;

    public function setUp()
    {
        $this->loader = new ConfigLoader;
        $this->loader->loadFromSymbol(true); // force loading

        $bootstrap = new Bootstrap($this->loader);
        $bootstrap->init();
    }

    public function testFindSchemasByClassNames()
    {
        $paths = $this->loader->getSchemaPaths();
        $this->assertNotEmpty($paths);
        $this->assertTrue(is_array($paths));
        $schemas = SchemaUtils::findSchemasByArguments($this->loader, array('TestApp\\Model\\UserSchema'));
        $this->assertNotEmpty($paths);
    }
}



