<?php
use LazyRecord\ConfigLoader;

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
        ok(is_array($paths));

        $schemas = LazyRecord\Schema\SchemaUtils::findSchemasByArguments($this->loader, array('TestApp\\Model\\UserSchema'));
        $this->assertNotEmpty($paths);
    }
}



