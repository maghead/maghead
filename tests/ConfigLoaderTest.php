<?php
use Maghead\ConfigLoader;

class ConfigLoaderTest extends PHPUnit_Framework_TestCase
{
    public function testLoadFromSymbol()
    {
        $config = new ConfigLoader;
        $config->loadFromSymbol(true);
        $config->setDefaultDataSourceId('mysql');
        $this->assertEquals('mysql', $config->getDefaultDataSourceId());
    }


    public function testSetConfigStash()
    {
        $config = new ConfigLoader;
        $config->setLoaded(true);
        $config->setConfigStash(array( 'schema' => array( 'auto_id' => true ) ));
    }
}
