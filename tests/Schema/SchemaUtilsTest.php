<?php
use Maghead\ConfigLoader;

class SchemaUtilsTest extends PHPUnit_Framework_TestCase
{
    public $loader;

    public function setUp()
    {
        $this->loader = new ConfigLoader;
        $this->loader->loadFromSymbol(true); // force loading
        $this->loader->initForBuild();
    }

    public function testFindSchemasByClassNames()
    {
        $paths = $this->loader->getSchemaPaths();
        $this->assertNotEmpty($paths);
        $this->assertTrue(is_array($paths));
        $schemas = Maghead\Schema\SchemaUtils::findSchemasByArguments($this->loader, array('TestApp\\Model\\UserSchema'));
        $this->assertNotEmpty($paths);
    }
}



