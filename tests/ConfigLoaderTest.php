<?php
use LazyRecord\ConfigLoader;

class ConfigLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testSetConfigStash()
    {
        $config = new ConfigLoader;
        $config->setLoaded(true);
        $config->setConfigStash(array( 'schema' => array( 'auto_id' => true ) ));
        ok($config);
    }

    public function testLoadFromSymbol()
    {
        $config = new ConfigLoader;
        $config->loadFromSymbol();
        $this->assertTrue($config->loaded);
    }



}

